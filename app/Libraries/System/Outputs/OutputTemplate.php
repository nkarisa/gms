<?php 

namespace App\Libraries\System\Outputs;

use Config\GrantsConfig;

class OutputTemplate {
    protected $controller;
    protected $action;
    protected $id;
    protected $request;
    protected $libs;
    protected $config;
    protected $currentLibrary;
    protected $read_db;
    protected $write_db;
    protected $module;
    protected $currentModel;
    protected $uri;

    function __construct($module)
    {
        $this->module = $module;
        $this->uri = service('uri');
        $segments = $this->uri->getSegments();

        $this->controller = isset($segments[0]) ? $segments[0] : 'dashboard';
        $this->action = isset($segments[1]) ? $segments[1] : 'list';
        $this->id = isset($segments[2]) ? $segments[2] : 0;

        $this->request = service('request');

        $this->libs = service('grantslib');

        $this->config = config(GrantsConfig::class);

        $this->currentLibrary = $this->libs->loadLibrary($this->controller);

        $this->currentModel = $this->libs->loadModel($this->controller);

        $this->read_db = \Config\Database::connect('read');
        $this->write_db = \Config\Database::connect('write');

        // $modelFileName = ucfirst($this->controller).'Model'; 
        // if (class_exists("App\\Models\\" . ucfirst($this->module) . "\\" . $modelFileName)) {
        //     $class = "App\\Models\\" . ucfirst($this->module) . "\\" . $modelFileName;

        //     $this->currentModel = new $class();
        // }

        // $libraryFileName = ucfirst($this->controller).'Library'; 
        // if (class_exists("App\\Libraries\\" . ucfirst($this->module) . "\\" . $libraryFileName)) {
        //     $class = "App\\Libraries\\" . ucfirst($this->module) . "\\" . $libraryFileName;

        //     $this->currentLibrary = new $class();
        // }
    }
}