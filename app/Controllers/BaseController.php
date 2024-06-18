<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Config\GrantsConfig;
use CodeIgniter\CLI\CLI;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    protected $session;
    
    /**
     * Write database connection properties
     */
    protected $write_db;

    /**
     * Read database connection properties
     */
    protected $read_db;
    protected $controller;
    protected $action;
    protected $id;
    protected $config;
    protected $uri;
    protected $max_status_id;
    protected $segments;
    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        $this->session = \Config\Services::session();

        // Load default helpers
        helper(['grants','form']);

        // Load database
        $this->read_db = \Config\Database::connect('read');
        $this->write_db = \Config\Database::connect('write');

        // Set a maintainance mode session 
        $this->maintainanceModeCheck();

        // Load grants config
        $this->config = config(GrantsConfig::class);

        // Set controller, action and ids
        $this->uri = service('uri');
        // $this->controller = $this->uri->getSegment(1) ?? 'dashboard';
        // $this->action = $this->uri->getSegment(2) ?? 'list';
        // $this->id = $this->uri->getSegment(3) ?? 0;
        $this->segments = $this->uri->getSegments();

        $this->controller = isset($this->segments[0]) ? $this->segments[0] : 'dashboard';
        $this->action = isset($this->segments[1]) ? $this->segments[1] : 'list';
        $this->id = isset($this->segments[2]) ? $this->segments[2] : 0;

        // Only run these if not cli request
        if (!is_cli()) {
            $this->sessionBasedConstructorSet();
        }

    }

    private function sessionBasedConstructorSet()
    {
    
        if ($this->action == 'view') {
            if ($this->session->has('master_table')) {
                $this->session->remove('master_table');
            }
            $this->session->set('master_table', ucfirst($this->controller));
            $this->id = hash_id(isset($this->segments[2]) ? $this->segments[2] : null, 'decode'); // Not sure what's this line does
        } elseif ($this->action == 'single_form_add' && count($this->uri->getSegments()) == 4) {
            if ($this->session->has('master_table')) {
                $this->session->remove('master_table');
            }
            $this->session->set('master_table', $this->uri->getSegment(4));
            $this->id = hash_id(isset($this->segments[2]) ? $this->segments[2] : 0, 'decode'); // Not sure what's this line does
        } elseif ($this->action == 'list') {
            $this->session->set('master_table', null);
            $this->id = hash_id(isset($this->segments[2]) ? $this->segments[2] : 0, 'decode'); 
        }
    
        $this->id = isset($this->segments[2]) ? $this->segments[2] : 0;
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $this->max_status_id = $statusLibrary->getMaxApprovalStatusId($this->controller);
    }
    

/**
 * This function checks the maintainance mode status from the database and sets the session variable.
 *
 * @return void
 */
private function maintainanceModeCheck(){
    // Instantiate the SettingModel
    $settingModel = new \App\Models\Core\SettingModel();

    // Fetch all settings from the database
    $settings = $settingModel->all();

    // Set the default value of maintenance_mode in the session to 0
    $this->session->set('maintenance_mode',0);
    
    // If the maintenance_mode is enabled in the database, set the session variable to 1
    if($settings['maintenance_mode'] == 1){
        $this->session->set('maintenance_mode',1);
    }
}

}
