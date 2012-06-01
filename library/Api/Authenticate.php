<?php

/**
 * Login model for user authentication
 *
 */
class Api_Authenticate implements Zend_Auth_Adapter_Interface
{

    const PROFILE = 'profile';

    const MASTER = 64;
    const SIGNED = 32;
    const USER = 2;
    const GUEST = 1;

    protected $_email, $_password, $_config, $_db;
    protected $_data = null;
    protected $_result = null;
    protected $_messages = array();
    protected $_is_internal = false;
    protected $_code = 0;

    protected static $_labels = array(
        self::MASTER => 'MASTER',
        self::SIGNED => 'SIGNED (valid signature must be provided)',
        self::USER => 'USER (valid token, or email/password provided)',
        self::GUEST => 'GUEST'
    );

    /**
     * Sets the posted username and password
     * @return void
     */
    public function __construct($email, $password)
    {
        $this->_email = trim(strtolower($email));
        $this->_password = trim($password);
        $this->_db = Zend_Registry::get('db');

    }

    /**
     * @static
     * @param  int $resource bitmap
     * @return bool
     */
    public static function checkResource($resource)
    {
        $profile = Zend_Registry::get(self::PROFILE);
        $profile_role = empty($profile['role']) ? self::GUEST : $profile['role'];
        return ($resource & $profile_role) ? true : false;

    }


    public static function assertResource($resource)
    {
        $profile = Zend_Registry::get(self::PROFILE);
        $profile_role = empty($profile['role']) ? self::GUEST : $profile['role'];

        if (!($resource & $profile_role)) {
            $required = self::getRoles($resource);
            throw new Exception('The following access levels are required for this resource: ' . implode(' or ', $required) . '');
        }

    }

    public static function getRoles($resource)
    {
        $roles = array();
        foreach (self::$_labels as $role => $label) {
            if ($resource & $role) {
                $roles[] = $label;
            }
        }
        return $roles;
    }

    public static function assertUser()
    {
        return self::assertResource(self::USER);
    }


    /**
     * Performs an auth attempt against the database
     * @return bool
     */
    public function authenticate()
    {

        $this->_config = Zend_Registry::get('config');


        if (empty($this->_email) && empty($this->_password)) {

            $this->_code = Zend_Auth_Result::FAILURE;
            $this->_data = false;
            $this->_messages[] = $this->getMessage($this->_code);

        } else {

            // Place authentication logic here

            $authenticated = true;

            if (empty($authenticated)) {
                $this->_code = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
                $this->_data = false;
                $this->_messages[] = $this->getMessage($this->_code);
            } else {

                $this->_code = Zend_Auth_Result::SUCCESS;
                $this->_data = $authenticated;
                $allProducts = Model_Product::getAll();
                $this->_messages[] = $this->getMessage($this->_code);

            }

        }

        $this->_result = new Zend_Auth_Result($this->_code, $this->_data, $this->_messages);
        return $this->_result;

    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_data->affiliate_id;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @return boolean
     */
    public function userExists()
    {
        return !(Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND == $this->_code);
    }


    /**
     * Get friendly message for Zend_Auth_Result
     * @return string
     */
    public function getMessage()
    {

        switch ($this->_code) {

            case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                $message = 'API request signature is invalid.';
                break;

            case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                $message = 'The email/password or token provided does not match a valid account. Please try again.';
                break;

            case Zend_Auth_Result::FAILURE:
                $message = 'You must enter a valid token, or *both* a valid email address and a valid password.';
                break;

            case Zend_Auth_Result::SUCCESS:
                $message = 'Successful authentication.';
                break;

            default:
                $message = 'Unable to authenticate.';
                break;
        }

        return $message;
    }

}
