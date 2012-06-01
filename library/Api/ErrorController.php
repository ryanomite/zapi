<?php

require_once('Api/Controller.php');

class Api_ErrorController extends Api_Controller
{
	public function errorAction()
	{
		$errors = $this->_getParam('error_handler');
		$exception = $errors->exception;
		
		$code = $exception->getCode();
		$message = $exception->getMessage();
		$errorDetails = array();
		
		switch ($errors->type) {
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				$code = 404;
				$message = 'Resource not found';
				break;
			default:
				$errorDetails = $exception->getDetails();
				
				switch ($code) {
					case 400:
					case 401:
					case 404:
						$message = $exception->getMessage();
						break;
					case 500:
					default:
						$code = 500;
						$message = 'Internal application error';
						break;
					
				}
				
				break;
		}
				
		$this->status = 0;
		$this->message = $message;
        $this->code = $code;
		$this->_response->setHttpResponseCode($code);
	}
	
	public function accessAction()
	{
		$this->status = 0;
		$this->message = 'Insufficient access';
	}
}
