<?php

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

