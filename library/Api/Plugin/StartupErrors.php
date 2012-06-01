<?php

class Api_Plugin_StartupErrors extends Zend_Controller_Plugin_Abstract
{

    // Ryan's hack - to throw an error before action is dispatched, just set 'error' key in registry

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {

        if (Zend_Registry::isRegistered('error') && ($exception = Zend_Registry::get('error'))) {

            $error = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
            $error->exception = $exception;
            $error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
            $error->request = clone $this->getRequest();
            $request->setControllerName('Error');
            $request->setParam('error_handler',$error);

        }
        return;

    }


}
