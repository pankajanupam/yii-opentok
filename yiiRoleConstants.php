<?php

class yiiRoleConstants {
    const SUBSCRIBER = "subscriber"; //Can only subscribe
    const PUBLISHER = "publisher";   //Can publish, subscribe, and signal
    const MODERATOR = "moderator";   //Can do the above along with  forceDisconnect and forceUnpublish
};
?>
