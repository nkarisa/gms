<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Config\GrantsConfig;

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
    protected $libs;
    protected $has_permission = false;
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
        $this->segments = $this->uri->getSegments();

        $this->controller = isset($this->segments[0]) ? $this->segments[0] : 'dashboard';
        $this->action = isset($this->segments[1]) ? $this->segments[1] : 'list';
        $this->id = isset($this->segments[2]) ? $this->segments[2] : 0;

        // Only run these if not cli request
        if (!is_cli()) {
            $this->sessionBasedConstructorSet();
        }

        // Load system libraries 
        $this->libs = service('grantslib');

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

public function result($id = ''){

    $output = [];

    if($this->id == null){
        $output  = $this->libs::call($this->controller.'.'.$this->action.'Output');
    }else{
        $output  = $this->libs::call($this->controller.'.'.$this->action.'Output', [$this->id]);
    }

    return $output;
} 

public function page_name():string {
    if ((hash_id($this->id, 'decode') == null && $this->action == 'view') || !$this->has_permission) {
        return 'error';
      } else {
        return $this->action;
      }
}

function page_title(): string
{
  $title = $this->action == 'list' ? $this->action.'_'.plural($this->controller) : $this->action.'_'.plural($this->controller);
  return get_phrase($title);
}

public function views_dir():string {
    $view_path = strtolower($this->controller);
    $page_name = $this->page_name();

    if (file_exists(VIEWPATH . $view_path . '/' . $this->session->user_account_system_code . '/' . $page_name . '.php') && $this->has_permission) {
      $view_path .= '/' . $this->session->user_account_system;
    } elseif (!file_exists(VIEWPATH . $view_path . '/' . $page_name . '.php') || !$this->has_permission) {
      $view_path =  'components';
    }

    // log_message('error', json_encode($view_path));

    return $view_path;
}

function crud_views(String $id = ''):string
{

  $result = $this->result($id);
  
  // Page name, Page title and views_dir can be overrode in a controller
  $page_data['page_name'] = $this->page_name();
  $page_data['page_title'] = $this->page_title();
  $page_data['views_dir'] = $this->views_dir();
  $page_data['result'] = $result;
//   $page_data['show_add_button'] = $this->libs::call($this->controller.'.showAddButton');

  // Can be overrode in a specific controller
  return view('general/index', ['output' => $page_data]);
}

public function list(){
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'read');
    return $this->crud_views();
}

public function single_form_add(){
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'create');
    return $this->crud_views();
}

public function multi_form_add(){
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'create');
    return $this->crud_views();
}

public function edit(){
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'update');
    return $this->crud_views();
}

public function delete(){
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'delete');
    return $this->libs::call('system.grants.delete', [$this->id]);
}

public function update(){
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'update');
    return $this->libs::call('system.grants.update', [$this->id]);
}

public function create(){
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'create');
    return $this->libs::call('system.grants.add', [$this->id]);
}

public function show_list(){
    $results = $this->libs::call($this->controller.'.listOutput', [$this->id]);
    $draw = intval($this->request->getPost('draw'));
    
    log_message('error', json_encode($results));

    $records = [];
    $columns = $results['keys'];

    $cnt = 0;
    foreach ($results['table_body'] as $row) {
      $cols = 0;
      $primary_key = 0;
      foreach ($columns as $column) {
        if($column == strtolower($this->controller).'_id'){
          $primary_key = $row[$column];
          continue;
        }

          if (strpos($column, 'track_number') == true) {
            $track_number = '';
            if(
              $this->session->system_admin ||
              (
                $this->{$this->controller.'_model'}->show_list_edit_action($row) &&
                $this->libs->loadLibrary('user')->checkRoleHasPermissions(strtolower($this->controller),'update')
              )
            ){
              $track_number .= '<a href="'.base_url().strtolower($this->controller).'/edit/'.hash_id($primary_key, 'encode').'"><i class = "fa fa-pencil"></i></a>';
            }
            
            $track_number .= ' <a href="' . base_url() . $this->controller . '/view/' . hash_id($primary_key) . '">' . $row[$column] . '</a>';
            $row[$column] = $track_number;

          } elseif (strpos($column, '_is_') == true) {
            $row[$column] =  $row[$column] == 1 ? "Yes" : "No";
          } elseif ($results['fields_meta_data'][$column] == 'int' || $results['fields_meta_data'][$column] == 'decimal') {
            // Defense code to ignore non numeric values when lookup values method changes value type from numeric to non numeric
            $row[$column] = is_numeric($row[$column]) ? number_format($row[$column], 2) : $row[$column];
          } else {
            $row[$column] = ucfirst(str_replace("_", " ", $row[$column]));
          }

          $records[$cnt][$cols] = $row[$column];
        // }

        $cols++;
      }
      $cnt++;
    }

    $response = array(
      'draw' => $draw,
      'recordsTotal' => $results['total_records'],
      'recordsFiltered' => $results['total_records'],
      'data' => $records
    );

    log_message('error',json_encode($response));
    return $this->response->setJSON($response);
}

}
