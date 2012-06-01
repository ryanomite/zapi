<?php

class Api_Plugin_AclCheck extends Zend_Controller_Plugin_Abstract
{

	protected $_db;
	
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {

    }
    
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
	    $acl = Zend_Registry::get('acl');
        $role = 'guest';
        if (Zend_Registry::isRegistered('profile')) {
            $profile = Zend_Registry::get('profile');
            $role = $profile['role'];

        }

		$target = '/' . $request->getControllerName() . '/' . $request->getActionName();

		if($acl->has($target) && (!$acl->isAllowed($role,$target))) {
			$request->setControllerName('error');
			$request->setActionName('access');
		}
	}
    
}
