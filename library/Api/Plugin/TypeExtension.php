<?php

class Api_Plugin_TypeExtension extends Zend_Controller_Plugin_Abstract
{

	protected $_db;
	
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {

        $url = preg_replace('/\.[a-z]+$/','',$_SERVER['REQUEST_URI']);
        $url = preg_replace('/\.[a-z]+(\?.*)$/','$1',$url);
		$request->setRequestUri($url);

    }

    
}
