<?php

require_once('Api/Controller.php');

/**
 * This class should be left abstract since instantiating it and calling the actions will result in errors (no object in the $_model space).
 */
abstract class Api_SimpleRestController extends Api_Controller
{
	protected $_model = null;
	protected $_entityName = null;
	
	/**
	 * Color me paranoid, but I need to make sure the methods I call on $_model actually exist.
	 */
	protected function _setModel(PublisherPlatform_Model $model)
	{
		$this->_model = $model;
	}
	
	protected function _setEntityName($name)
	{
		$this->_entityName = $name;
	}
	
	public function getAction()
	{
		$this->data = $this->_model->populateByKey($this->_params)->toArray();
		$this->message = $this->_entityName . ' found';
	}
	
	public function postAction()
	{
		try {
			$this->_model->coldUpdate($this->_params);
			$this->message = $this->_entityName . ' updated';
		} catch (PublisherPlatform_Exception_InvalidKey $ik) {
			$this->data = $this->_model->easyCreate($this->_params)->toArray();
			$this->message = $this->_entityName . ' created';
		}
	}
}
