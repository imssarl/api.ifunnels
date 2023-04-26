<?php

class Core_Services_Twilio_Rest_Conference
    extends Core_Services_Twilio_InstanceResource
{
    protected function init($client, $uri)
    {
        $this->setupSubresources(
            'participants'
        );
    }
}
