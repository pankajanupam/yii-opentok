yii-opentok
===========
OpenTok php library for the Yii Framework

**NOTE: NOT really usable lib**

##Configure
First of all there is an need to configure the `CApplicationComponent` in your `config/main.php` like the following example:

    <?php
    return array(
        ...
        'components' => array(
            ...
            'tok' => array(
                'class'  => 'ext.yii-opentok.EOpenTok',
                'key'    => 'KEY', //provided by your opentok account
                'secret' => 'SECRET', //provided by your opentok account
            ),
            ...
        ),
        ....
    );

Then in order to get the widget running you would have to create a session or retrieve a session from your "persistance" (cookie, session, db):
Bottom line the widget runs like this:

    <?php
    $sessionId = Yii::app()->tok->createSession()->id;

    $this->widget('EOpenTokWidget', array(
        'key'       => Yii::app()->tok->key,
        'sessionId' => $sessionId,
        'token'     => Yii::app()->tok->generateToken($sessionId),
    ));


**NOTES**
- Above code is not tested it is a raw example of how to use the extension
- EOpenTokWidget is the bear min to get nothing, you need to check the opentok documentation for your specific case.

##Resources
- [OpenTok Documentation](http://www.tokbox.com/opentok/api/documentation)
- [OpenTok REST API Documnetation](http://www.tokbox.com/opentok/api/tools/documentation/api/server_side_libraries.html)
- [Yii Framework](http://yiiframework.com) 
