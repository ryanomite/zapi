<?php

require_once('Api/Controller.php');

class Api_ModelController extends Api_Controller
{

    protected $_table = '';
    protected $_sequence = true;

    protected $_key = array();
    protected $_fields = array();
    protected $_required = array();
    protected $_defaults = array();

    protected $_db = null;
    protected $_tableModel = null;  // By default, Zend_Db_Table_Abstract, unless provided

    protected $_status = 1;
    protected $_message;
    protected $_request = null;
    protected $_params = null;
    protected $_profile = null;


    public function init()
    {
        $this->_db = Zend_Registry::get('db');

        // If _tableModel is not set, use Zend_Db_Table factory
        if (null === $this->_tableModel) {
            $this->_tableModel = new Zend_Db_Table($this->_table);
        }

        parent::init();
    }

    public function getAction()
    {
        $key = $this->__getKey( $this->_params );
        $where = $this->__getWhere( $key );
        $this->data = $this->_tableModel->fetchAll( $where[0] );
    }

    public function postAction()
    {

        $arrParams = $this->__filterParams( $this->_params );
        $key = $this->__getKey( $this->_params );

        // If key is missing, assume insert
        if (empty($key)) {

            $arrParams = array_merge($this->_defaults, $arrParams);
            $missingParams = array_diff( $this->_required, array_keys($arrParams) );

            if (!empty($missingParams)) {
                $this->status = 0;
                $this->message = 'Missing required parameter(s): ' . implode(', ',$missingParams);
            } else {
                $this->_tableModel->insert($arrParams);
                if ($this->_sequence) {
                    $this->id = $this->_db->lastInsertId();
                }
                $this->message = 'Record created';
            }

        } else {
            $where = $this->__getWhere($key);
            $this->_tableModel->update( array_diff_key($arrParams,$key),$where);
            $this->message = 'Record updated';
        }

    }

    /**
     * Returns either an array of primary key fields from input parameters, or false, if
     * a complete key is not present.
     * @param  array $arrParams
     * @return array|bool
     */
    private function __getKey($arrParams)
    {

        $key = false;

        // If single-column key, and ID is provided, use that
        if (isset($arrParams['id']) && (count($this->_key) == 1)) {
            $key = array_combine($this->_key,array($arrParams['id']));
        } else {
            $keys = array_intersect_key($arrParams, array_flip($this->_key));
            if (count($keys) == count($this->_key)) $key = $keys;
        }

        return $key;

    }

    /**
     * Removes any parameters that are not in $this->_fields
     * @param  $arrParams
     * @return array
     */
    private function __filterParams($arrParams)
    {
        return array_intersect_key($arrParams,array_flip($this->_fields));
    }


    /**
     * Returns a Zend Select filtering on a set of parameters
     * @param  $table
     * @param  $arrParams
     * @return Zend_Select
     */
    private function __getWhere($arrParams)
    {

        // Filter
        $where = array();
        foreach ($arrParams as $field => $val) {
            $where[] = $this->_db->quoteInto("$field = ?",$val);
        }
        return $where;

    }

}