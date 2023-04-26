<?php

class Core_Services_Twilio_Rest_Queues
    extends Core_Services_Twilio_ListResource
{
    /**
     * Create a new Queue
     *
     * @param string $friendly_name The name of this queue
     * @param array $params A list of optional parameters, and their values
     * @return Core_Services_Twilio_Rest_Queue The created Queue
     */
    function create($friendly_name, array $params = array()) {
        return parent::_create(array(
            'FriendlyName' => $friendly_name,
        ) + $params);
    }
}

