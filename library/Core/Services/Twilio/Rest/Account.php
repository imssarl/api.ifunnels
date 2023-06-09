<?php

class Core_Services_Twilio_Rest_Account extends Core_Services_Twilio_InstanceResource {

    protected function init($client, $uri) {
        $this->setupSubresources(
            'applications',
            'available_phone_numbers',
            'outgoing_caller_ids',
            'calls',
            'conferences',
            'incoming_phone_numbers',
            'notifications',
            'outgoing_callerids',
            'recordings',
            'sms_messages',
            'short_codes',
            'transcriptions',
            'connect_apps',
            'authorized_connect_apps',
            'usage_records',
            'usage_triggers',
            'queues'
        );

        $this->sandbox = new Core_Services_Twilio_Rest_Sandbox(
            $client, $uri . '/Sandbox'
        );
    }
}
