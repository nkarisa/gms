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


class WebController extends BaseController
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
  protected $subAction = null;
  protected $library;
  protected $settings;
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
    helper(['grants', 'form', 'elements']);

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
    $this->action = isset($this->segments[1]) && !$this->request->isAJAX() ? $this->segments[1] : 'list';
    $this->id = isset($this->segments[2]) && !$this->request->isAJAX() ? $this->segments[2] : 0;
    $this->subAction = isset($this->segments[4]) && !$this->request->isAJAX() ? $this->segments[4] : null;

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

  }

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
      $this->id = hash_id(isset($this->segments[2]) ? $this->segments[2] : 0, 'decode'); // Not sure what's this line does
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
   * This function checks the maintainance mode status from the database and sets the session variable.
   *
   * @return void
   */
  private function maintainanceModeCheck()
  {
    // Instantiate the SettingModel
    $settingModel = new \App\Models\Core\SettingModel();

    // Fetch all settings from the database
    $settings = $settingModel->all();

    // Set the default value of maintenance_mode in the session to 0
    $this->session->set('maintenance_mode', 0);

    // If the maintenance_mode is enabled in the database, set the session variable to 1
    if ($settings['maintenance_mode'] == 1) {
      $this->session->set('maintenance_mode', 1);
    }
  }

  public function result($id = '', $parentTable = null)
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

  public function page_name(): string
  {
    if ((hash_id($this->id, 'decode') == null && $this->action == 'view') || !$this->has_permission) {
      return 'error';
    } else {
      return $this->action;
    }
  }

  function page_title(): string
  {
    $title = $this->action == 'list' ? $this->action . '_' . plural($this->controller) : $this->action . '_' . plural($this->controller);
    return get_phrase($title);
  }

  public function views_dir(): string
  {
    $view_path = strtolower($this->controller);
    $page_name = $this->page_name();

    if (file_exists(VIEWPATH . $view_path . '/' . $this->session->user_account_system_code . '/' . $page_name . '.php') && $this->has_permission) {
      $view_path .= '/' . $this->session->user_account_system;
    } elseif (!file_exists(VIEWPATH . $view_path . '/' . $page_name . '.php') || !$this->has_permission) {
      $view_path = 'components';
    }

    return $view_path;
  }

  public function user_info(): array
  {
    $primary_user_data_id = 0;
    $session = session();
    $user_can_read_switch = true;
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

  public function navigation(): string
  {
    $menuLibrary = new \App\Libraries\Core\MenuLibrary();
    $navItems = $menuLibrary->navigationItems();
    return $navItems;
  }

  function crud_views(string $id = null, $parentTable = null): string|\CodeIgniter\HTTP\Response
  {
    $result = $this->result($id);

    // Page name, Page title and views_dir can be overrode in a controller
    $page_data['page_name'] = $this->page_name();
    $page_data['page_title'] = $this->page_title();
    $page_data['views_dir'] = $this->views_dir();
    $page_data['action'] = $this->action;
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

    if ($this->action == 'list') {
      $show_add_button = $this->libs::call($this->controller . '.checkShowAddButton', [$this->controller]);
      $keys = $this->libs->toggleListSelectColumns();
      $page_data['keys'] = $keys;
      $page_data['is_multi_row'] = $this->libs::call($this->controller . '.checkIfTableIsMultiRow');
      $page_data['has_details_table'] = $this->libs->checkIfTableHasDetailTable($this->controller);
      $page_data['has_details_listing'] = $this->libs->checkIfTableHasDetailListing($this->controller);
      $page_data['show_add_button'] = $show_add_button;
    }

    // Can be overrode in a specific controller
    return view('general/index', $page_data);
  }

  public function list()
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'read');
    return $this->crud_views();
  }

  public function view($id)
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'read');
    return $this->crud_views();
  }

  public function singleFormAdd($parentId = null, $parentTable = null)
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'create');
    return $this->crud_views($parentId, $parentTable);
  }

  public function multiFormAdd()
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'create');
    return $this->crud_views();
  }

  public function create($parentId = null, $parentTable = null)
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'create');
    return $this->libs::call("$this->controller.add", [$parentId, $parentTable]);
  }

  public function edit()
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'update');
    return $this->crud_views();
  }

  public function update($id)
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'update');
    return $this->libs::call("$this->controller.edit", [$this->id]);
  }

  public function delete()
  {
    $this->has_permission = $this->libs->loadLibrary('user')->checkRoleHasPermissions(ucfirst($this->controller), 'delete');
    return $this->libs::call('system.grants.delete', [$this->id]);
  }

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
    $data = $results['table_body'];
    $total_records = $results['total_records'];

    $records = [];
    $columns = $results['keys'];

    $cnt = 0;
    foreach ($data as $row) {
      $cols = 0;
      $primary_key = 0;
      foreach ($columns as $column) {
        if ($column == strtolower($this->controller) . '_id') {
          $primary_key = $row[$column];
          continue;
        }

        if (strpos($column, 'track_number') == true) {
          $track_number = '';
          if (
            $this->session->system_admin ||
            (
              $this->library->showListEditAction($row) &&
              $this->libs->loadLibrary('user')->checkRoleHasPermissions(strtolower($this->controller), 'update')
            )
          ) {
            $track_number .= '<a href="' . base_url() . strtolower($this->controller) . '/edit/' . hash_id($primary_key, 'encode') . '"><i class = "fa fa-pencil"></i></a>';
          }

          $track_number .= ' <a href="' . base_url() . $this->controller . '/view/' . hash_id($primary_key) . '">' . $row[$column] . '</a>';
          $row[$column] = $track_number;

        } elseif (strpos($column, '_is_') == true) {
          $row[$column] = $row[$column] == 1 ? "Yes" : "No";
        } elseif ($results['fields_meta_data'][$column] == 'int' || $results['fields_meta_data'][$column] == 'decimal') {
          $row[$column] = is_numeric($row[$column]) ? number_format($row[$column], 2) : $row[$column];
        } else {
          $row[$column] = $row[$column] != null ? ucfirst(str_replace("_", " ", $row[$column])) : $row[$column];
        }

        if (method_exists($this->library, 'formatColumnsValues')) {
          $row[$column] = $this->library->formatColumnsValues($column, $row[$column]);
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

  public function ajax(?string $controller = "", ?string $method = "", ...$args): ResponseInterface
  {
    // Post keys should be: controller, method and data
    // All ajax request will be received here and passed to library of a controller
    // All ajax responses MUST have a status key with either success or failed
    // When using GET ajax, from the 3rd argument, the paramters MUST be paired with odd positioned parameters as keys and even positioned parameters as values

    log_message('error', json_encode($this->request->getPost()));

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
}
