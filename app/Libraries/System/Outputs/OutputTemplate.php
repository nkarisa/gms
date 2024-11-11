<?php 

namespace App\Libraries\System\Outputs;

use Config\GrantsConfig;
use App\Libraries\System\AccessBaseLibrary;

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
    protected $access = null;
    protected $subAction = null;

    function __construct($module = "")
    {
        $this->module = $module;
        $this->uri = service('uri');
        $this->request = service('request');
        $segments = $this->uri->getSegments();

        $this->controller = isset($segments[0]) ? $segments[0] : 'dashboard';
        $this->action = isset($segments[1]) && !$this->request->isAJAX() ? $segments[1] : 'list';
        $this->id = isset($segments[2]) && !$this->request->isAJAX()  ? $segments[2] : 0;
        $this->subAction = isset($segments[3]) && !$this->request->isAJAX()  ? $segments[3] : null; //$this->uri->segment(4, null);;

        $this->libs = service('grantslib');

        $this->config = config(GrantsConfig::class);

        $this->currentLibrary = $this->libs->loadLibrary($this->controller);

        $this->currentModel = $this->libs->loadModel($this->controller);

        $this->read_db = \Config\Database::connect('read');
        $this->write_db = \Config\Database::connect('write');

        $this->access = new AccessBaseLibrary();

        if($module == ""){
            $modules = json_decode(service("settings")->get("GrantsConfig.modules"));
            $modelFileName = ucfirst($this->controller).'Model'; 
        
            foreach($modules as $moduleName){
                if (class_exists("App\\Models\\" . ucfirst($moduleName) . "\\" . $modelFileName)) {
                    $class = "App\\Models\\" . ucfirst($moduleName) . "\\" . $modelFileName;

                    $this->currentModel = new $class();
                }

                $libraryFileName = ucfirst($this->controller).'Library'; 
                if (class_exists("App\\Libraries\\" . ucfirst($moduleName) . "\\" . $libraryFileName)) {
                    $class = "App\\Libraries\\" . ucfirst($moduleName) . "\\" . $libraryFileName;

                    $this->currentLibrary = new $class();
                }
            }
        }
    }
}