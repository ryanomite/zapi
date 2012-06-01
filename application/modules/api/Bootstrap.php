<?php
/**
 * User: Ryan Roper <rroper@adknowledge.com>
 * Date: 5/27/11
 * Time: 11:06 AM
 */
 
class Api_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected function _initModels()
    {
        set_include_path(implode(PATH_SEPARATOR, array(
            dirname(__FILE__) . '/models',
            get_include_path()
        )));
    }
}

