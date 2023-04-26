<?php

class Core_Services_Twilio_Rest_Accounts
    extends Core_Services_Twilio_ListResource
{
    public function create(array $params = array())
    {
        return parent::_create($params);
    }
}
