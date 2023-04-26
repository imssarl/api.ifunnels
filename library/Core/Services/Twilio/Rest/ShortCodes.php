<?php

class Core_Services_Twilio_Rest_ShortCodes
    extends Core_Services_Twilio_ListResource
{
    public function __construct($client, $uri) {
        $uri = preg_replace("#ShortCodes#", "SMS/ShortCodes", $uri);
        parent::__construct($client, $uri);
    }
}
