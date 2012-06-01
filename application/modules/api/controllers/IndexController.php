<?php

class IndexController extends Api_Controller
{

    public function getAction()
    {
        $this->messages = 'Please provide a specific API in your URL.';
    }

}

