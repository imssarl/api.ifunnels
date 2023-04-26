<?php

class Core_Services_Twilio_Rest_Participant
    extends Core_Services_Twilio_InstanceResource
{
    public function mute()
    {
        $this->update('Muted', 'true');
    }
}
