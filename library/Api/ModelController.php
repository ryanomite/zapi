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
    protected $_tableModel = null; // By default, Zend_Db_Table_Abstract, unless provided

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
        // If fields are defined for this ModelController, allow filter against those
        if (!empty($this->_fields)) {
            $allParams = array_merge(array_filter((array)$this->_profile), $this->_params);
            $fieldParams = array_intersect_key($allParams, array_flip($this->_fields));
            $where = $this->_tableModel->select();
            foreach ($fieldParams as $col => $val) {
                $where->where($col . ' = ?', $val);
            }
        } else {
            // Otherwise, filter on key only
            $key = $this->__getKey($this->_params);
            $where = $this->__getWhere($key);
            $where = $where[0];
        }

        // only return these columns
        if ($columns = $this->_request->getParam('columns')) {
            $where->from($this->_table, (array)$columns);
        }

        $data = $this->_tableModel->fetchAll($where)->toArray();

        $disableFilter = $this->_request->getParam('disable_filter', false);
        if (!$disableFilter) {
            $this->data = $this->__filterParams($data, $this->_fields);
        } else {
            $this->data = $data;
        }
    }

    public function postAction()
    {
        $id = $this->_insert();
        $this->message = 'Record saved';

        // return object
        $key = $this->__getKey(array('id' => $id));
        $where = $this->__getWhere($key);
        $where = $where[0];

        $data = $this->_tableModel->fetchAll($where)->toArray();

        if ($data) {
            $this->data = $this->__filterParams($data[0], $this->_fields);
        }
    }

    public function deleteAction()
    {
        $this->_delete();
        $this->message = 'Record deleted';
    }

    public function putAction()
    {
        $this->_delete();
        $this->_insert();
        $this->message = 'Record replaced';
    }

    protected function _insert()
    {
        $arrParams = $this->__filterParams($this->_params);
        $key = $this->__getKey($this->_params);

        // If key is missing, assume insert
        if (empty($key)) {

            $arrParams = array_merge($this->_defaults, $arrParams);
            $missingParams = array_diff($this->_required, array_keys($arrParams));

            if (!empty($missingParams)) {
                throw new Exception('Missing required parameter(s): ' . implode(', ', $missingParams));
            } else {
                $this->_tableModel->insert($arrParams);
                if ($this->_sequence) {
                    $this->id = $this->_db->lastInsertId();

                    // Set key-named parameter
                    foreach ((array)$this->_key as $key) {
                        $this->$key = $this->id;
                    }
                    return $this->id;
                }
            }

        } else {
            $where = $this->__getWhere($key);
            $this->_tableModel->update(array_diff_key($arrParams, $key), $where);

            if (count($this->_key) === 1) {
                return $key[current($this->_key)];
            }
        }

        return true;
    }

    protected function _delete()
    {
        $arrParams = $this->__filterParams($this->_params);
        $key = $this->__getKey($this->_params);

        // If key is missing, assume insert
        if (empty($key)) {
            throw new Exception('Must provide a filter' . implode(',', $this->_key));
        } else {
            $where = $this->__getWhere($key);
            $this->_tableModel->delete($where);
        }
    }

    /**
     * Returns either an array of primary key fields from input parameters, or false, if
     * a complete key is not present.
     * @param  array $arrParams
     * @return array|bool
     */
    protected function __getKey($arrParams)
    {

        $key = false;

        // If single-column key, and ID is provided, use that
        if (isset($arrParams['id']) && (count($this->_key) == 1)) {
            $key = array_combine($this->_key, array($arrParams['id']));
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
        // filter through array of rows or if single row do array_intersect
        if (is_array(current($arrParams))) {
            $data = array();

            foreach ($arrParams as $row) {
                $data[] = array_intersect_key($row, array_flip($this->_fields));
            }

            return $data;
        } else {
            return array_intersect_key($arrParams, array_flip($this->_fields));
        }
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
            $where[] = $this->_db->quoteInto("$field = ?", $val);
        }
        return $where;

    }

}
