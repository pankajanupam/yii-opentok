<?php

class yiiOpenTokWidget extends CWidget
{
    public $apiKey;
    
    public $sessionId;
    
    public $token;

    public $publisherContainerId;
    
    public $subscribeContainerId;
   
    public function init()
    {
        Yii::app()->clientScript->registerScriptFile('https://static.tokbox.com/v1.1/js/TB.min.js', CClientScript::POS_HEAD);
       
        Yii::app()->clientScript->registerScript(__CLASS__.":".$this->id, <<<EOF

            var apiKey    = "{$this->apiKey}";
            var sessionId = "{$this->sessionId}";
            var token     = "{$this->token}";
            var pCid      = "{$this->publisherContainerId}";
            var sCid      = "{$this->subscribeContainerId}";

            function sessionConnectedHandler (event) {
               session.publish( publisher );
               subscribeToStreams(event.streams);
            }
            function subscribeToStreams(streams) {
              for (var i = 0; i < streams.length; i++) {
                  var stream = streams[i];
                  if (stream.connection.connectionId 
                         != session.connection.connectionId) {
                          session.subscribe(stream,sCid,
                                             {width:600, height:300})
                  }
              }
            }
            function streamCreatedHandler(event) {
              subscribeToStreams(event.streams);
            }

            var publisher = TB.initPublisher(apiKey,pCid,{width:300, height:300});
            var session   = TB.initSession(sessionId);

            session.connect(apiKey, token);
            session.addEventListener("sessionConnected",sessionConnectedHandler);
            session.addEventListener("streamCreated",streamCreatedHandler);
EOF
        );
    }

}