<?php

class yiiOpenTokSession{

    public $sessionId;
    public $sessionProperties;

    function __construct($sessionId, $properties) {
        $this->sessionId = $sessionId . '';
        $this->sessionProperties = $properties;
    }

    public function __toString() {
        return $this->sessionId;
    }

    public function getSessionId() {
        return $this->sessionId;
    }

}

class SessionPropertyConstants {
    const P2P_PREFERENCE = 'p2p.preference';
}

?>
