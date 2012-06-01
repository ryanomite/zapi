<?php

class Api_Plugin_AclCheck extends Zend_Controller_Plugin_Abstract
{

	protected $_db;
	
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {

    }
    
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
	}
    
}
