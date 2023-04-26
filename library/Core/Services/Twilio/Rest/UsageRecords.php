<?php

class Core_Services_Twilio_Rest_UsageRecords extends Core_Services_Twilio_TimeRangeResource {

    public function init($client, $uri) {
        $this->setupSubresources(
            'today',
            'yesterday',
            'all_time',
            'this_month',
            'last_month',
            'daily',
            'monthly',
            'yearly'
        );
    }
}

class Core_Services_Twilio_Rest_Today extends Core_Services_Twilio_TimeRangeResource { } 

class Core_Services_Twilio_Rest_Yesterday extends Core_Services_Twilio_TimeRangeResource { }

class Core_Services_Twilio_Rest_LastMonth extends Core_Services_Twilio_TimeRangeResource { }

class Core_Services_Twilio_Rest_ThisMonth extends Core_Services_Twilio_TimeRangeResource { }

class Core_Services_Twilio_Rest_AllTime extends Core_Services_Twilio_TimeRangeResource { }

class Core_Services_Twilio_Rest_Daily extends Core_Services_Twilio_UsageResource { }

class Core_Services_Twilio_Rest_Monthly extends Core_Services_Twilio_UsageResource { }

class Core_Services_Twilio_Rest_Yearly extends Core_Services_Twilio_UsageResource { }
