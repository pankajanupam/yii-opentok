<?php
Yii::import('ext.yii-opentok.yiiOpenTokSession');
Yii::import('ext.yii-opentok.yiiRoleConstants');
Yii::import('ext.yii-opentok.yiiOpenTokWidget');

class yiiOpenTokSDK  extends CApplicationComponent{
    
    /**
     * @var string api key 
     */
    public $apiKey;
    
    /**
     * @var string api secret
     */
    public $apiSecret;
    
    /**
     * @var string serverUrl 
     */
    public $serverUrl = "http://api.opentok.com/hl";

    
    public function init()
    {
        if($this->apiKey===null || $this->apiSecret===null) {
            throw CException('OpentTok API key and secret where not provided');
        }
        parent::init();
    }
 
    /**
     * Creates a new session.
     * $location - IP address to geolocate the call around.
     * $properties - Optional array, keys are defined in SessionPropertyConstants
     */
    public function createSession($location='', $properties=array()) {
        $properties["location"] = $location;
        $properties["apiKey"] = $this->apiKey;

        $createSessionResult = $this->_do_request("/session/create", $properties);
        $createSessionXML = @simplexml_load_string($createSessionResult, 'SimpleXMLElement', LIBXML_NOCDATA);
        
        if(!$createSessionXML) {
            throw CException("Failed to create session: Invalid response from server");
        }

        $errors = $createSessionXML->xpath("//error");
        if($errors) {
            $errMsg = $errors[0]->xpath("//@message");
            if($errMsg) {
                $errMsg = (string)$errMsg[0]['message'];
            } else {
                $errMsg = "Unknown error";
            }
            throw CException("Error " . $createSessionXML->error['code'] ." ". $createSessionXML->error->children()->getName() . ": " . $errMsg );
        }
        
        if(!isset($createSessionXML->Session->sessionId)) {
            echo"<pre>";print_r($createSessionXML);echo"</pre>";
            throw CException("Failed to create session.");
        }
        
        $sessionId = $createSessionXML->Session->sessionId;

        return new yiiOpenTokSession($sessionId, null);
    }
    
    /** - Generate a token
     *
     * $sessionId  - If sessionId is not blank, this token can only join the call with the specified sessionId.
     * $role        - One of the constants defined in yiiRoleConstants. Default is publisher, look in the documentation to learn more about roles.
     * $expire_time - Optional timestamp to change when the token expires. See documentation on token for details.
     * $connection_data - Optional string data to pass into the stream. See documentation on token for details.
     */
    public function generateToken($sessionId='', $role='', $expire_time=NULL, $connection_data='') {
        $create_time = time();

        $nonce = microtime(true) . mt_rand();

        if(is_null($sessionId) || strlen($sessionId) == 0){
            throw CException("Null or empty session ID are not valid");
        }

        $sub_sessionId = substr($sessionId, 2);
        $decoded_sessionId="";
        for($i=0;$i<3;$i++){
            $new_sessionId = $sub_sessionId.str_repeat("=",$i);
            $new_sessionId = str_replace("-", "+",$new_sessionId);
            $new_sessionId = str_replace("_", "/",$new_sessionId);
            $decoded_sessionId = base64_decode($new_sessionId);
            if($decoded_sessionId){
                break;
            }
        }
        if (strpos($decoded_sessionId, "~")===false){
            throw CException("An invalid session ID was passed");
        }else{
            $arr=explode("~",$decoded_sessionId);
            if($arr[1]!=$this->apiKey){
                throw CException("An invalid session ID was passed");
            }
        }

        if(!$role) {
            $role = yiiRoleConstants::PUBLISHER;
        } else if (!in_array($role, array(yiiRoleConstants::SUBSCRIBER, 
                yiiRoleConstants::PUBLISHER, yiiRoleConstants::MODERATOR))) {
            throw CException("unknown role $role");
        }

        $data_string = "sessionId=$sessionId&create_time=$create_time&role=$role&nonce=$nonce";
        if(!is_null($expire_time)) {
            if(!is_numeric($expire_time))
                throw CException("Expire time must be a number");
            if($expire_time < $create_time)
                throw CException("Expire time must be in the future");
            if($expire_time > $create_time + 2592000)
                throw CException("Expire time must be in the next 30 days");
            $data_string .= "&expire_time=$expire_time";
        }
        if($connection_data != '') {
            if(strlen($connection_data) > 1000)
                throw CException("Connection data must be less than 1000 characters");
            $data_string .= "&connection_data=" . urlencode($connection_data);
        }

        $sig = $this->_sign_string($data_string, $this->apiSecret);
        $apiKey = $this->apiKey;

        return "T1==" . base64_encode("partner_id=$apiKey&sig=$sig:$data_string");
    }
    
    //////////////////////////////////////////////
    //Signing functions, request functions, and other utility functions needed for the OpenTok
    //Server API. Developers should not edit below this line. Do so at your own risk.
    //////////////////////////////////////////////

    protected function _sign_string($string, $secret) {
        return hash_hmac("sha1", $string, $secret);
    }

    protected function _do_request($url, $data, $auth = array('type' => 'partner')) {
        $url = $this->serverUrl . $url;

        $dataString = "";
        foreach($data as $key => $value){
            $value = urlencode($value);
            $dataString .= "$key=$value&";
        }

        $dataString = rtrim($dataString,"&");

        switch($auth['type']) {
            case 'token':
                $authString = "X-TB-TOKEN-AUTH: ".$auth['token'];
                break;
            case 'partner':
            default:
                $authString = "X-TB-PARTNER-AUTH: $this->apiKey:$this->apiSecret";
                break;
        }

        //Use file_get_contents if curl is not available for PHP
        if(function_exists("curl_init")) {            
            $ch = curl_init();

            $apiKey = $this->apiKey;
            $apiSecret = $this->apiSecret;

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Content-type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_HTTPHEADER, Array($authString));   
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

            $res = curl_exec($ch);
            if(curl_errno($ch)) {
                throw new RequestException('Request error: ' . curl_error($ch));
            }

            curl_close($ch);
        }
        else {        
            if (function_exists("file_get_contents")) {
                $context_source = array ('http' => array (
                                        'method' => 'POST',
                                        'header'=> Array("Content-type: application/x-www-form-urlencoded", $authString, "Content-Length: " . strlen($dataString), 'content' => $dataString)
                                        )
                                    );
                $context = stream_context_create($context_source);
                $res = @file_get_contents( $url ,false, $context);                
            }
            else{
                throw new RequestException("Your PHP installion neither supports the file_get_contents method nor cURL. Please enable one of these functions so that you can make API calls.");
            }        
        }        
        return $res;
    }
    
    
    /** - Old functions to be depreciated...
     */
    public function generate_token($sessionId='', $role='', $expire_time=NULL, $connection_data='') {
      return $this->generateToken($sessionId, $role, $expire_time, $connection_data);
    } 
    public function create_session($location='', $properties=array()) {
      return $this->createSession($location, $properties);
    }
}