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

    function __construct()
    {
        $uri = service('uri');
        $segments = $uri->getSegments();

        $this->controller = isset($segments[0]) ? $segments[0] : 'dashboard';
        $this->action = isset($segments[1]) ? $segments[1] : 'list';
        $this->id = isset($segments[2]) ? $segments[2] : 0;

        $this->request = service('request');

        $this->libs = service('grantslib');

        $this->config = config(GrantsConfig::class);

        $this->currentLibrary = $this->libs->loadLibrary($this->controller);

        $this->read_db = \Config\Database::connect('read');
        $this->write_db = \Config\Database::connect('write');
    }
}