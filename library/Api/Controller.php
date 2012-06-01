<?php

class Api_Controller extends Zend_Controller_Action
{

    protected $_data = array();
    protected $_status = 1; // OK, by default
    protected $_message;
    protected $_code = 200; // OK
    protected $_time = 0;
    protected $_request = null;
    protected $_params = null;
    protected $_profile = null;
    protected $_loggers = null;
    protected $_registry = null;
    protected $_messages = false;
    protected $_db = null;

    public function init()
    {
        $this->_request = $this->getRequest();
        $this->_profile = Zend_Registry::get(Api_Authenticate::PROFILE);
        $this->_params = $this->_request->getParams();
        $this->_loggers = null;
        $this->_registry = Zend_Registry::getInstance();
        $this->_db = $this->_registry->get('db');
        $this->_time = time();

        //$this->profile = $this->_profile; // for Debugging
    }

    public function indexAction()
    {
        $this->_forward('get');
    }

    public function output($data = false, $status = false)
    {
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        if (false !== $data) $this->_data = $data;
        if (false !== $status) $this->_status = $status;


        // Import any messages from registry

        if ($this->_registry->isRegistered('messages')) {
            $this->_messages = $this->_messages ? array_merge($this->_registry->get('messages'), (array)$this->_messages) : $this->_registry->get('messages');
        }

        // If data is neither 2D nor scalar (i.e. one-dimensional array/object, or structured), add status


        $view->data = $this->_data;
        $view->response = array();
        $view->response['status'] = $this->_status;
        $view->response['data'] = $this->_data;
        $view->response['code'] = $this->_code;
        $view->response['messages'] = $this->_messages;
        $view->response['time'] = $this->_time;

    }

    public function __set($name, $value)
    {

        if (strlen($name) > 1 && substr($name, 0, 1) != '_') {
            switch ($name) {
                case 'status':
                    $this->_status = $value;
                    break;
                case 'code':
                    $this->_code = $value;
                    break;
                case 'message':
                    $this->addMessage($value);
                    break;
                case 'messages':
                    $this->addMessage($value);
                    break;
                case 'time':
                    $this->_time = $value;
                    break;
                case 'data':
                    $this->_data = $value;
                    break;
                default:
                    $this->_data[$name] = $value;
            }
        }
    }

    public function __get($name)
    {
        if ('status' == $name) {
            return $this->_status;
        } elseif ('data' == $name) {
            return $this->_data;
        } else {
            return isset($this->_data[$name]) ? $this->_data[$name] : null;
        }
    }

    public function addMessage($value, $type = 0)
    {
        switch (gettype($value)) {
            case 'array':
                break;
            default:
                $value = array('type' => $type, 'message' => (string)$value);
        }
        if (!$this->_messages) $this->_messages = array();
        $this->_messages[] = $value;

    }

    /**
     * @static
     * @param mixed $data
     * @return bool
     *
     * Iterates through API dataset, and returns true if data is scalar, an array of scalars, or an object of scalar properties
     * Used to determine if data can be outputted to CSV, text, or Excel
     */
    protected function _is2D($data = false)
    {

        if (false === $data) {
            $data = $this->_output['data'];
        }
        if (is_scalar($data)) {
            return false;
        } else {
            $data = (array)$data;

            foreach ($data as $i => $v1) {
                if (is_scalar($v1)) {
                    return false;
                } else {
                    foreach ($v1 as $i => $v2) {
                        if (!is_scalar($v2) && !self::_is2D($v2)) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Public wrapper, for API layout use/filtering
     */
    public static function is2D($data = false)
    {
        return self::_is2D($data);
    }

    public function postDispatch()
    {
        $this->output();
    }

}