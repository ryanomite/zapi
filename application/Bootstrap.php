<?php
/**
 * Zapi
 *
 * LICENSE
 *
 * Copyright (c) 2012 Ryan Roper <zapi@ryanroper.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <organization> nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected $_db;
    protected $_cache = false;
    protected $_caches = false;
    protected $_session = false;
    protected $_profile = false;
    protected $_registry = false;
    protected $_config = false;
    protected $_acl = false;

    protected function _initLoad()
    {
        $loader = Zend_Loader_Autoloader::getInstance();
        $loader->registerNamespace('Api_');
        $loader->registerNamespace('XML_');
        $loader->suppressNotFoundWarnings(true);
    }

    protected function _initRegistry()
    {
        $this->_registry = Zend_Registry::getInstance();
    }

    protected function _initConfig()
    {
        $config = new Zend_Config($this->getOptions());
        $this->_registry->set('config', $config);
        $this->_config = $config;
    }

    protected function _initDb()
    {
        if (isset($this->_config->db)) {
            try {
                $connections = new ArrayObject;

                foreach ($this->_config->db->connections as $dbId => $db) {
                    // using zend_db to setup connection but not using it for the app
                    $conn = Zend_Db::factory($db);
                    $connections->$dbId = $conn;
                    if ($dbId == @$this->_config->db->default) {
                        Zend_Db_Table::setDefaultAdapter($conn);
                        $this->_registry->set('db', $conn);
                        $this->_db = $conn;
                    }
                }

                $this->_registry->set('dbs', $connections);
            } catch (Exception $e) {
                $this->__throwError(new Exception('Cannot connect to required database; please try again later.'));
            }
        } else {
            $this->_db = null;
            $this->_dbs = array();
            $this->_registry->set('db', null);
        }
    }

    protected function _initCache()
    {
        $connections = new ArrayObject;

        foreach ($this->_config->cache->connections as $type => $config) {
            if (empty($_GET['nocache']) && !empty($config->enabled)) {
                try {
                    switch ($type) {
                        case 'zend_server_shm':
                            $backendConfig = $config->backend->toArray();
                            $backend = new Zend_Cache_Backend_ZendServer_ShMem($backendConfig);
                            break;

                        case 'apc':
                            $backend = new Zend_Cache_Backend_Apc();
                            break;

                        case 'memcache':
                            $backendConfig = $config->backend->toArray();
                            $backend = new Zend_Cache_Backend_Memcached($backendConfig);
                            break;
                    }

                    $frontendConfig = $config->frontend->toArray();
                    $frontend = new Zend_Cache_Core($frontendConfig);
                    $cache = Zend_Cache::factory($frontend, $backend);

                    $connections->$type = $cache;
                    if ($type == @$this->_config->cache->default) {
                        $this->_registry->set('cache', $cache);
                        $this->_cache = $cache;
                    }
                } catch (Exception $e) {

                }
            }
        }

        $this->_registry->set('caches', $connections);
    }


    protected function _initProfile()
    {

        /** Three goals:
         *
         *   1. Authenticate (password, or token)
         *   2. Determine a role (i.e. 'account', 'user', 'product', 'admin')
         *   3. Export profile:
         *        user_id, account_id, product_id, role
         **/
        $username = empty($_REQUEST['username']) ? (empty($_REQUEST['email']) ? false : $_REQUEST['email']) : $_REQUEST['username'];
        $password = empty($_REQUEST['password']) ? (empty($_REQUEST['token']) ? false : $_REQUEST['token']) : $_REQUEST['password'];
        $key = md5($username . '|' . $password);

        if (empty($this->_cache) || (false == ($profile = $this->_cache->load($key)))) {

            $authAdapter = new Api_Authenticate($username, $password);
            $authResult = $authAdapter->authenticate();

            if ($authResult->isValid()) {
                $profile = $authResult->getIdentity();
                if (!empty($profile['user_id'])) {
                    $profile['role'] = Api_Authenticate::USER | Api_Authenticate::GUEST;
                }
            } else {
                // Set default profile
                $profile = array('user_id' => null, 'role' => Api_Authenticate::GUEST, 'token' => null);

                // Throw error if user is trying to authenticate
                if (!empty($username) || !empty($password)) {
                    $this->__throwError(new Exception($authAdapter->getMessage()));
                }
            }

            if ($this->_cache) {
                $this->_cache->save($profile, $key);
            }

        }

        $this->_profile = $profile;
        $this->_registry->set(Api_Authenticate::PROFILE, $profile);

    }


    protected function _initType()
    {
        // Pull type out of URL (as extension), if possible (e.g. /ping.xml)
        if (preg_match('/\.([a-z]+)\??.*$/', $_SERVER['REQUEST_URI'], $m)) {
            $in_type = strtolower($m[1]);
        } else {
            $in_type = isset($_GET['type']) ? strtolower($_GET['type']) : (isset($_POST['type']) ? strtolower($_POST['type']) : '');
        }

        // 'callback' parameter is indicator of JSONP format
        if (isset($_GET['callback'])) {
            $in_type = 'jsonp';
            $this->_registry->set('callback', $_GET['callback']);
        }

        if (!in_array($in_type, $this->_config->rest->types->toArray())) {
            $in_type = $this->_config->rest->defaulttype;
        }

        // Setup View
        $layout = Zend_Layout::startMvc(array(
            'layoutPath' => APPLICATION_PATH . '/layouts/scripts',
            'layout' => $in_type
        ));

        Zend_Registry::set('type', $in_type);
        $view = $layout->getView();

        //$layout->_helper->viewRenderer('action');

        if (isset($this->_config->global)) {
            foreach ($this->_config->global as $key => $val) {
                $view->$key = $val;
            }
        }
    }


    protected function _initMVC()
    {
        $this->bootstrap('frontController');
        $this->_front = Zend_Controller_Front::getInstance();
        $this->_front->throwExceptions(null);
        //$this->_front->registerPlugin(new Api_Plugin_Signature);
        $this->_front->registerPlugin(new Api_Plugin_TypeExtension);
        $this->_front->registerPlugin(new Api_Plugin_StartupErrors);
        $this->_front->addModuleDirectory($this->_config->resources->frontController->moduleDirectory);

        $this->_front->setDefaultModule('api');

        $restful = new Api_Plugin_RestRoute($this->_front);
        $this->_front->getRouter()->addRoute('api', $restful);
        $this->_front->setParam('noViewRenderer', true);
        $this->_registry->set('front', $this->_front);
    }

    private function __throwError($exception)
    {
        $this->_registry->set('error', $exception);
    }
}