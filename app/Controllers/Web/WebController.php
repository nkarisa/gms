<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use Psr\Log\LoggerInterface;
use Config\GrantsConfig;
use App\Libraries\System\GrantsLibrary;
use \App\Traits\System;


class WebController extends BaseController
{

  use System\BuilderTrait;
  use System\LibraryInitTrait;
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
  protected $subAction = null;
  protected $library;
  protected $settings;
  
  /**
 * Initializes the controller with necessary configurations and dependencies.
 *
 * This method sets up the controller by loading required services, helpers, 
 * databases, and configurations. It also determines the current controller, 
 * action, and ID based on the URL segments.
 *
 * @param RequestInterface $request The current HTTP request
 * @param ResponseInterface $response The HTTP response
 * @param LoggerInterface $logger The logger instance
 * @return void
 */
  public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
  {
    // Do Not Edit This Line
    parent::initController($request, $response, $logger);

    $this->initBuilders();
    // $this->initLibraries();
    
    // Preload any models, libraries, etc, here.

    $this->session = \Config\Services::session();

    // Load default helpers
    helper(['grants', 'form', 'elements']);

    // Load database
    $this->read_db = \Config\Database::connect('read');
    $this->write_db = \Config\Database::connect('write');

    // Load grants config
    $this->config = config(GrantsConfig::class);

    // Set controller, action and ids
    $this->uri = service('uri');
    $this->segments = $this->uri->getSegments();

    $this->controller = isset($this->segments[0]) ? $this->segments[0] : 'dashboard';
    $this->action = isset($this->segments[1]) ? $this->segments[1] : 'list';
    $this->id = isset($this->segments[2]) ? $this->segments[2] : 0;
    $this->subAction = isset($this->segments[4]) ? $this->segments[4] : null;

    if($this->request->isAJAX()){
      if($this->controller == "ajax" || $this->controller == "ajaxRequest"){
        $this->controller = isset($this->segments[1]) ? $this->segments[1] : 'dashboard';
      }

      if($this->action == "showList"){
        $this->action = 'list';
      }
      // $this->action = null;
      // $this->id = null;
      // $this->subAction = null;
    }
    
    // Only run these if not cli request
    if (!is_cli()) {
      $this->sessionBasedConstructorSet();
    }

    // Load system libraries 
    $this->libs = service('grantslib');

    $this->settings = service('settings');

    if ($this->controller != "login" && $this->controller != "ajax") {
      $this->library = $this->libs->loadLibrary($this->controller);
    }

    $this->read_db->query("SET sql_mode = ''");
    $this->read_db->query("SET sql_mode = ''");
    $this->read_db->query("SET sql_mode = ''");
  }


  /**
 * Sets up session-based constructor variables and initializes user locale.
 *
 * This method handles the setup of session-based variables, particularly the 'masterTable',
 * based on the current action and URI segments. It also initializes the max status ID and
 * sets the user locale.
 *
 * @return void
 */
  private function sessionBasedConstructorSet()
  {

    $lib = new GrantsLibrary();

    if ($this->session->has('masterTable')) {
      $this->session->remove('masterTable');
    }


    if ($this->action == 'view' && $lib->checkIfTableHasDetailTable($this->controller)) {
      $this->session->set('masterTable', ucfirst($this->controller));
      $this->id = hash_id(isset($this->segments[2]) ? $this->segments[2] : null, 'decode'); // Not sure what's this line does
    } elseif ($this->action == 'singleFormAdd' && count($this->uri->getSegments()) == 4) {
      $this->session->set('masterTable', $this->uri->getSegment(4));
      // log_message('error', $this->session->masterTable);
      // $this->id = $this->uri->getSegment(3);
      $this->id = isset($this->segments[2]) ? $this->segments[2] : null; // Used for example when adding a newr permission to a role
    } elseif ($this->action == 'list') {
      $this->session->set('masterTable', null);
      $this->id = hash_id(isset($this->segments[2]) ? $this->segments[2] : 0, 'decode');
    }

    $this->id = isset($this->segments[2]) ? $this->segments[2] : 0;
    $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    $this->max_status_id = $statusLibrary->getMaxApprovalStatusId($this->controller);

    // Manually initialize the user locale
    $locale = $this->session->get('user_locale') ? $this->session->get('user_locale') : service('settings')->get('App.defaultLocale');
    $this->request->setLocale($locale);
  }

/**
 * Retrieves the data for the specified action and id.
 *
 * This method is responsible for fetching the data required for the specified action and id.
 * It checks if the action is "list" and if the request is an AJAX request, it calls the appropriate method to fetch the data for datatable serverside processing.
 * If the action is not "list", it calls the appropriate method to fetch the data for the specified id.
 *
 * @param string $id The id of the record to fetch data for.
 * @param string $parentTable The parent table of the record, if applicable.
 * @return array The data fetched for the specified action and id.
 */

  protected function result($id = '', $parentTable = null)
  {

    $output = [];

    if ($this->action == "list") {
      // List page data will only be loaded via ajax request and will be a datatable serverside loaded
      if ($this->request->isAJAX()) {
        $output = $this->libs::call($this->controller . '.' . $this->action . 'Output', [$id, $parentTable]);
      }
    } else {
      if ($this->id == null) {
        $output = $this->libs::call($this->controller . '.' . $this->action . 'Output');
      } else {
        $output = $this->libs::call($this->controller . '.' . $this->action . 'Output', [$this->id]);
      }
    }

    return $output;
  }

/**
 * Retrieves the page name based on the current state of the controller.
 *
 * This method determines the page name based on the current state of the controller. 
 * It returns error pages for users who do not have permission to view the page.
 *
 * @return string The action name or "view_ajax" if the action is "view" and the request is an AJAX request.
 */
  protected function page_name(): string
  {
    if ((hash_id($this->id, 'decode') == null && $this->action == 'view') || (!$this->has_permission && !$this->session->has('primary_user_data'))) {
      return 'error';
    } else {
      return $this->action;
    }
  }

  /**
   * This method retrieves the page title based on the current state of the controller.
   * @return string
   */
  private function page_title(): string
  {
    $title = service("settings")->get('GrantsConfig.systemName');//$this->action == 'list' ? humanize($this->action) . '_' . plural($this->controller) : humanize($this->action) . '_' . $this->controller;
    
    $plural = get_phrase(underscore(plural(humanize($this->controller))), plural(humanize($this->controller)));
    $singular = get_phrase($this->controller, humanize($this->controller));

    if($this->action == 'list'){
      $title = get_phrase('list') . ' ' . $plural;
    }elseif($this->action == 'singleFormAdd' || $this->action == 'multiFormAdd'){
      $title = get_phrase('add') . ' ' . $singular;
    }else{
      $title = get_phrase($this->action) . ' ' . $singular;
    }

    return $title;
  }

  /**
   * The method retrieves the views directory based on the current state of the controller.
   * @return string
   */
  private function views_dir(): string
  {
    $view_path = strtolower($this->controller);
    $page_name = $this->page_name();
    if (file_exists(VIEWPATH . $view_path . '/' . $this->session->user_account_system_code . '/' . $page_name . '.php') && $this->has_permission) {
      $view_path .= '/' . $this->session->user_account_system_code;
    } elseif (!file_exists(VIEWPATH . $view_path . '/' . $page_name . '.php') || (!$this->has_permission && !$this->session->has('primary_user_data'))) {
      $view_path = 'components';
    }

    return $view_path;
  }

  /**
   * Prepare user infomation of the current user logged and aunthenticating the user.
   * @return array
   */
  private function user_info(): array
  {
    $session = session();
    $primary_user_data_id = $this->session->has('primary_user_data') ? $this->session->primary_user_data['user_id'] : 0;
    $user_can_read_switch = $this->libs->loadLibrary('user')->checkRoleHasPermissions('user_switch', 'read');
    $languageLibrary = new \App\Libraries\Core\LanguageLibrary();
    $user_available_languages = $languageLibrary->getUserAvailableLanguages();
    $user_locale = $session->get('user_locale');
    $default_language = $languageLibrary->getDefaultLanguage();

    $user_icon = '2.png';

    $user = [
      'user_id' => $session->get('user_id'),
      'name' => $session->get('name'),
      'primary_user_data_id' => $primary_user_data_id,
      'user_can_read_switch' => $user_can_read_switch,
      'user_available_languages' => $user_available_languages,
      'user_locale' => $user_locale,
      'default_language' => $default_language,
      // 'text_align' => $text_align,
      'user_icon' => $user_icon,
    ];

    return $user;
  }

  /**
   * Retrieves the navigation items for the current user.
   * @return string
   */
  private function navigation(): string
  {
    $menuLibrary = new \App\Libraries\Core\MenuLibrary();
    $navItems = $menuLibrary->navigationItems();
    return $navItems;
  }

  /**
   * Builds what to render back to the views
   * @param string $id
   * @param mixed $parentTable
   * @return string|\CodeIgniter\HTTP\Response
   */
  private function crud_views(string $id = null, $parentTable = null): string|\CodeIgniter\HTTP\Response
  {
    $result = $this->result($id);

    // Page name, Page title and views_dir can be overrode in a controller
    $page_data['page_name'] = $this->page_name();
    $page_data['page_title'] = $this->page_title();
    $page_data['views_dir'] = $this->views_dir();
    $page_data['action'] = $this->page_name();
    $page_data['result'] = $result;
    $page_data['text_align'] = 'left-to-right';  // Set in the settings config
    $page_data['skin_colour'] = 'green'; // Set in the settings config
    $page_data['controller'] = $this->controller;
    $page_data['id'] = $this->id;
    $page_data['uri'] = $this->uri;
    $page_data['user'] = $this->user_info();
    $page_data['navigation'] = $this->navigation();
    $page_data['session'] = session();
    $page_data['config'] = $this->config;
    $page_data['libs'] = $this->libs;
    $page_data['settings'] = $this->settings;
    $page_data['subAction']= $this->subAction;

    if ($this->action == 'list') {
      $show_add_button = $this->libs::call($this->controller . '.checkShowAddButton', [$this->controller]);
      $keys = $this->columnAliases();

      $page_data['keys'] = $keys;
      $page_data['is_multi_row'] = $this->libs::call($this->controller . '.checkIfTableIsMultiRow');
      $page_data['has_details_listing'] = $this->libs->checkIfTableHasDetailListing($this->controller);
      $page_data['show_add_button'] = $show_add_button;
    }

    // Can be overrode in a specific controller
    return view('general/index', $page_data);
  }

  function columnAliases(): array{
    $columns = array_values($this->libs->getListColumns());

    $tableName = $this->controller;
    $featureLib = $this->libs->loadLibrary($tableName);
    $primaryKeyField = $this->libs->primaryKeyField($tableName);

    if (method_exists($featureLib, 'columnAliases')) {
      $aliases = $featureLib->columnAliases();
      
      $key_values = array_map(function($key) use($primaryKeyField){
        $humanize = humanize($key);
        if($key == $primaryKeyField){
          $humanize = $key;
        }
        return $humanize;
      },$columns);
      
      $flipped_keys = array_combine($columns,$key_values);
      $intersected_keys_aliases = array_intersect_key($aliases, $flipped_keys);
      
      if(sizeof($intersected_keys_aliases)){
        $columns = array_replace($flipped_keys, $intersected_keys_aliases);
      }
    }

    return $columns;
  }

  /**
   * Get list of records from the database and load its view
   * @return string|\CodeIgniter\HTTP\Response
   */
  public function list()
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'read');
    return $this->crud_views();
  }

  /**
   * Get a single record from the database and load its view
   * @param mixed $id
   * @return string|\CodeIgniter\HTTP\Response
   */
  public function view($id)
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'read');
    return $this->crud_views();
  }

  /**
   * Get a view for adding a new record
   * @param mixed $parentId
   * @param mixed $parentTable
   * @return string|\CodeIgniter\HTTP\Response
   */
  public function singleFormAdd($parentId = null, $parentTable = null)
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'create');
    return $this->crud_views($parentId, $parentTable);
  }

  /**
   * Get a view for adding multiple records
   * @return string|\CodeIgniter\HTTP\Response
   */
  public function multiFormAdd()
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'create');
    return $this->crud_views();
  }

  /**
   * Recieve a record saving request
   * @param mixed $parentId
   * @param mixed $parentTable
   * @return mixed
   */
  public function create($parentId = null, $parentTable = null)
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'create');
    return $this->libs::call("$this->controller.add", [$parentId, $parentTable]);
  }

  /**
   * Get a view for editing a record
   * @return string|\CodeIgniter\HTTP\Response
   */
  public function edit()
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'update');
    return $this->crud_views();
  }

  /**
   * Get a record editing request
   * @param mixed $id
   * @return mixed
   */
  public function update($id)
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'update');
    return $this->libs::call("$this->controller.edit", [$this->id]);
  }

  /**
   * Get a record deleting request
   * @return mixed
   */
  public function delete()
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'delete');
    return $this->libs::call('system.grants.delete', [$this->id]);
  }

  /**
   * Load datatable serverside listing rew records and the related datatable serverside processing responses
   * @return \CodeIgniter\HTTP\ResponseInterface
   */
  public function showList(): ResponseInterface
  {

    $parentId = null;
    $parentTable = null;

    if ($this->request->getPost('parentId')) {
      $parentId = $this->request->getPost('parentId');
    }

    if ($this->request->getPost('parentTable')) {
      $parentTable = $this->request->getPost('parentTable');
    }

    $results = $this->result($parentId, $parentTable);
    $draw = intval($this->request->getPost('draw'));
    // $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    
    $data = $results['table_body'];
    $formatColumnValuesdependancyData = $this->library->formatColumnsValuesDependancyData($data);
    $showListEditActionDependancyData = $this->library->showListEditActionDependancyData($data);
    $total_records = $results['total_records'];

    $records = [];
    $columns = $results['keys'];

    $cnt = 0;
    // log_message('error', json_encode($data));
    foreach ($data as $row) {
      $cols = 0;
      $primary_key = 0;
      foreach ($columns as $column) {
        
        if (substr($column, -2) === "id" && $column == strtolower($this->controller) . '_id') {
          $primary_key = $row[$column];
          continue;
        }
        if (strpos($column, 'track_number') == true) {
          $track_number = '';
          if (
            $this->session->system_admin ||
            (
              $this->library->showListEditAction($row, $showListEditActionDependancyData) &&
              $this->libs->loadLibrary('user')->checkRoleHasPermissions(strtolower($this->controller), 'update')
            )
          ) {
            $track_number .= '<a href="' . base_url() . strtolower($this->controller) . '/edit/' . hash_id($primary_key, 'encode') . '"><i class = "fa fa-pencil"></i></a>';
          }

          $track_number .= ' <a href="' . base_url() . $this->controller . '/view/' . hash_id($primary_key) . '">' . $row[$column] . '</a>';
          $row[$column] = $track_number;

        } elseif (strpos($column, '_is_') == true) {
          $row[$column] = $row[$column] == 1 ? get_phrase('yes') : get_phrase('no');
        } elseif ($results['fields_meta_data'][$column] == 'int' || $results['fields_meta_data'][$column] == 'decimal') {
          $row[$column] = is_numeric($row[$column]) ? number_format($row[$column], 2) : $row[$column];
        } else {
          $row[$column] = $row[$column] != null ? ucfirst(str_replace("_", " ", $row[$column])) : $row[$column];
        }

        if (method_exists($this->library, 'formatColumnsValues')) {
          $row[$column] = $this->library->formatColumnsValues($column, $row[$column], $row, $formatColumnValuesdependancyData);
        }

        $records[$cnt][$cols] = $row[$column];
        $cols++;
      }
      $cnt++;
    }

    $response = [
      'draw' => intval($draw),
      'recordsTotal' => intval($total_records),
      'recordsFiltered' => intval($total_records),
      'data' => $records
    ];


    return $this->response->setJSON($response);
  }

  /**
   * Receives ajax requests from the client
   * @param mixed $controller
   * @param mixed $method
   * @param array $args
   * @return \CodeIgniter\HTTP\ResponseInterface
   */
  public function ajaxRequest(?string $controller = "", ?string $method = "", ...$args): ResponseInterface
  {
    // Post keys should be: controller, method and data
    // All ajax request will be received here and passed to library of a controller
    // All ajax responses MUST have a status key with either success or failed
    // When using GET ajax, from the 3rd argument, the paramters MUST be paired with odd positioned parameters as keys and even positioned parameters as values
    if ($controller && $method) {

      $data = [];

      // Check if the args has equal number of odd and even positioned arguments
      if (count($args) % 2 == 1) {
        return $this->response->setJSON(['status' => 'failed', "message" => "Odd number of arguments in ajax request"]);
      }

      // If there are arguments, then set them as an array
      if (count($args) > 0) {
        $data = [];
        for ($i = 0; $i < count($args); $i += 2) {
          $data[$args[$i]] = $args[$i + 1];
        }
        // Verify if all keys are not numeric keys
        if (array_filter(array_keys($data), 'is_numeric')) {
          return $this->response->setJSON(['status' => 'failed', "message" => "Numeric keys are not allowed in ajax request"]);
        }
      } else {
        $data = null;
      }

    } else {
      $vars = $this->request->getPost();
      extract($vars);
    }

    $controllerLibrary = $this->libs->loadLibrary($controller);
    $response = $controllerLibrary->{$method}($data);

    // If the method returns a response, add a message key with either success or failure
    if (isset($response['status'])) {
      return $this->response->setJSON($response);
    } else {
      $response['status'] = 'failed';
      $response['message'] = 'Wrongly formatted response';
      return $this->response->setJSON($response);
    }
  }

  /**
   * Receives an approval action from a user and changes the status of an item and update approvers list
   * @param mixed $item
   * @return void
   */
  public function updateItemStatus($item)
  {
    $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    $userLibrary = new \App\Libraries\Core\UserLibrary();

    // Check if <table_name>_approvers column exists if not create it
    $this->libs->createTableApproversColumns($item);
    $buttons = '<div class="badge badge-danger">' . get_phrase('approval_process_failed') . '</div>';
    $post = $this->request->getPost();

    $statusLibrary->createChangeHistoryOnStatusChange([$item . '_id' => $post['item_id'], 'fk_status_id' => $post['next_status']], [$item . '_id' => $post['item_id'], 'fk_status_id' => $post['current_status']], $item);

    if ($post['next_status'] > 0) {
      $account_system_id = $statusLibrary->getStatusAccountSystem($post['next_status']);
      $action_button_data = $this->libs->actionButtonData($item, $account_system_id);
      
      // Once the update is successful, complete post update events
      $itemLibrary = $this->libs->loadLibrary($item);
      if (method_exists($itemLibrary , 'postApprovalActionEvent')) {
        $itemLibrary ->postApprovalActionEvent([
          'item' => $item,
          'post' => $post
        ]);
      }

      $buttons = approval_action_button($item, $action_button_data['item_status'], $post['item_id'], $post['next_status'], $action_button_data['item_initial_item_status_id'], $action_button_data['item_max_approval_status_ids']);

      $data['fk_status_id'] = $post['next_status'];
      $data[$item.'_last_modified_by'] = $this->session->user_id;
      $data[$item.'_last_modified_date'] = date('Y-m-d h:i:s');
      $data[$item.'_approvers'] = $userLibrary->updateApproversList($this->session->user_id, $item, $post['item_id'], $post['current_status'], $post['next_status']);
      
      $builder = $this->write_db->table($item);
      $builder->where(array($item . '_id' => $post['item_id']));
      $builder->update($data);
    }

    echo $buttons;
  }

}
