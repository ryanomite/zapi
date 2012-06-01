<?php
/**
 * User: Ryan Roper <rroper@adknowledge.com>
 * Date: 5/24/11
 * Time: 11:15 AM
 */
 

class PingController extends Api_Controller
{

    public function init()
    {
        parent::init();
    }

    public function getAction()
    {
        $this->time = time();
    }


}

