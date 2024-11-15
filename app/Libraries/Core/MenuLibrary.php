<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\MenuModel;
use App\Libraries\Core\UniqueIdentifierLibrary;

class MenuLibrary extends GrantsLibrary
{

    protected $table;

    protected $menuModel;

    public function __construct()
    {
        parent::__construct();

        $this->menuModel = new MenuModel();

        $this->table = 'menu';
    }

    // public function multiSelectField(): string
    // {
    //     return '';
    // }

    // public function actionBeforeIinsert(array $postArray): array{
    //     return $postArray;
    // }
    /**
     * This function retrieves the user's menu items in their defined order.
     *
     * @return array An array of user menu items, each represented as an associative array.
     */
    public function getUserMenuItems()
    {
        // Initialize a database builder for the 'menu' table
        $builder = $this->read_db->table($this->table);

        // Select specific columns from the 'menu' table
        $builder->select(['menu_name', 'menu_derivative_controller', 'menu_user_order_priority_item']);

        // Join the 'menu_user_order' table on the 'menu_id' column
        $builder->join('menu_user_order', 'menu.menu_id = menu_user_order.fk_menu_id');

        // Order the results by 'menu_user_order_level' in ascending order, and then by 'menu_name'
        $builder->orderBy('menu_user_order_level ASC, menu_name');

        // If the user is not a system admin, filter the menu items based on 'menu_is_active'
        if (!session()->get('system_admin')) {
            $builder->where('menu_is_active', 1);
        }

        // Filter the menu items based on the user's ID and 'menu_user_order_is_active'
        $builder->where([
            'fk_user_id' => session()->get('user_id'),
            'menu_user_order_is_active' => 1
        ]);

        // Execute the query and retrieve the results
        $query = $builder->get();

        // Return the results as an array of associative arrays
        return $query->getResultArray();
    }

    /**
     * This function retrieves the count of user menu items.
     *
     * @return int The count of user menu items.
     */
    public function getCountOfUserMenuItems()
    {
        // Initialize a database builder for the 'menu_user_order' table
        $builder = $this->read_db->table('menu_user_order');

        // Filter the results based on the user's ID
        $builder->where('fk_user_id', session()->get('user_id'));

        // Execute the count query and return the result
        return $builder->countAllResults();
    }

    /**
     * This function retrieves the count of menu items.
     *
     * @return int The count of menu items.
     *
     * @throws Exception If there is a database error.
     */
    public function getCountOfMenuItems()
    {
        // Initialize a database builder for the 'menu' table
        $builder = $this->read_db->table($this->table);

        // If the user is not a system admin, filter the menu items based on 'menu_is_active'
        if (!session()->get('system_admin')) {
            $builder->where('menu_is_active', 1);
        }

        // Execute the count query and return the result
        // Throws an exception if there is a database error
        return $builder->countAllResults();
    }


    /**
     * This function retrieves the ID of the default menu item based on the default launch page.
     *
     * @return int|null The ID of the default menu item, or null if no default menu item is found.
     */
    public function getIdOfDefaultMenuItem()
    {
        // Query the database to find the menu item with the default launch page controller
        $result = $this->read_db->table($this->table)
            ->where('menu_derivative_controller', service("settings")->get("GrantsConfig.defaultLaunchPage"))
            ->get()
            ->getRow();

        // If a result is found, return the menu ID
        if ($result) {
            return $result->menu_id;
        } else {
            // If no result is found, return null or handle the case where no menu item is found
            return null;
        }
    }

    /**
     * This function synchronizes the user's menu order with the database.
     * If the number of user menu items does not match the number of menu items in the database,
     * it will update the user's menu order accordingly.
     *
     * @return array An array of user menu items, each represented as an associative array.
     */
    public function upsertUserMenu()
    {
        $session = session();

        // Get all menu elements, excluding inactive ones for non-admin users
        if (!$session->get('system_admin')) {
            $this->read_db->table($this->table)->where('menu_is_active', 1);
        }
        $menu_elements = $this->read_db->table($this->table)->get()->getResultArray();

        // Array of menu ids
        $menu_ids = array_column($menu_elements, 'menu_id');

        // Get the count of user menu items and menu items in the database
        $sizeOfUserMenuItems = $this->getCountOfUserMenuItems();
        $sizeOfMenuItemsByDatabase = $this->getCountOfMenuItems();

        $user_menu_data = [];

        // If the number of user menu items does not match the number of menu items in the database
        if ($sizeOfUserMenuItems !== $sizeOfMenuItemsByDatabase) {
            // Get the user's existing menu order items
            $menu_user_order_items = $this->read_db->table('menu_user_order')
                ->where('fk_user_id', $session->get('user_id'))
                ->get();

            $order = $menu_user_order_items->getNumRows();

            // Loop through each menu item
            foreach ($menu_ids as $menu_id) {
                // Set default priority to 1, unless it's the last item or exceeds the max priority
                $user_menu_data['menu_user_order_priority_item'] = 1;
                if (sizeof($menu_ids) - 1 == $order) {
                    $user_menu_data['menu_user_order_priority_item'] = 0;
                } elseif ($order > service("settings")->get("GrantsConfig.maxPriorityMenuItems") - 1) {
                    $user_menu_data['menu_user_order_priority_item'] = 0;
                }

                // If the menu item is the default menu item, set its priority to 1
                if ($this->getIdOfDefaultMenuItem() == $menu_id) {
                    $user_menu_data['menu_user_order_priority_item'] = 1;
                }

                $order++;

                // Set the user ID, menu ID, and order level for the user menu item
                $user_menu_data['fk_user_id'] = $session->get('user_id');
                $user_menu_data['fk_menu_id'] = $menu_id;
                $user_menu_data['menu_user_order_level'] = $order;

                // Check if the user menu item already exists in the database
                $existing_menu_item = $this->read_db->table('menu_user_order')
                    ->where(['fk_user_id' => $session->get('user_id'), 'fk_menu_id' => $menu_id])
                    ->get();

                // If the user menu item does not exist, insert it into the database
                if ($existing_menu_item->getNumRows() == 0) {
                    $this->read_db->table('menu_user_order')->insert($user_menu_data);
                }
            }
        }

        // Get user menu items in their user-defined order
        return $this->getUserMenuItems();
    }

    public function upsertMenu($menus)
    {

        foreach ($menus as $menu => $menuItems) {
            $data = [
                'menu_name' => $menu,
                'menu_derivative_controller' => $menu,
            ];

            if ($this->session->get('system_admin')) {
                //$db->where(['menu_is_active' => 1]);
            }

            $countOfActiveMenus = $this->read_db->table($this->table)
                ->where('menu_derivative_controller', $menu)
                ->countAllResults();

            if ($countOfActiveMenus == 0) {
                $this->write_db->table($this->table)->insert($data);

                $menuId = $this->write_db->insertID();
                $permissionData = [
                    'menu_id' => $menuId,
                    'table_name' => $menu,
                ];

                $this->add('permission', $permissionData);

                $approveItemLibrary = new ApproveItemLibrary();
                $statusLibrary = new StatusLibrary();

                $approveItemLibrary->insertMissingApproveableItem(strtolower($menu));
                $this->mandatoryFields(strtolower($menu));
                $statusLibrary->insertStatusIfMissing(strtolower($menu));
                $this->createResourceUploadDirectoryStructure();
            } else {
                $this->write_db->table($this->table)
                    ->where('menu_derivative_controller', $menu)
                    ->update($data);
            }
        }

        $arrMenu = array_column($this->read_db->table($this->table)->get()->getResultArray(), 'menu_derivative_controller');
        $removedControllers = array_diff($arrMenu, array_keys($menus));

        if (count($removedControllers) > 0) {
            foreach ($removedControllers as $removedController) {
                $this->write_db->transStart();

                $removedMenu = $this->read_db->table($this->table)
                    ->where('menu_derivative_controller', $removedController)
                    ->get()
                    ->getRow();

                $allMenuPermissions = $this->read_db->table('permission')
                    ->where('fk_menu_id', $removedMenu->menu_id)
                    ->get();

                if ($allMenuPermissions->getNumRows() > 0) {
                    foreach ($allMenuPermissions->getResult() as $permission) {
                        $permissionId = $permission->permission_id;

                        $this->write_db->table('role_permission')
                            ->where('fk_permission_id', $permissionId)
                            ->delete();
                    }
                }

                $this->write_db->table('permission')
                    ->where('fk_menu_id', $removedMenu->menu_id)
                    ->delete();

                $this->write_db->table($this->table)
                    ->where('menu_derivative_controller', $removedController)
                    ->delete();

                $this->write_db->transComplete();
            }
        }
    }

    public function getMenuItems(): array
    {

        $controllers = $this->getAllTables();

        $tablesNotRequiredInMenu = decode_setting("GrantsConfig","tablesNotRequiredInMenu");

        $controllers = array_diff($controllers, $tablesNotRequiredInMenu);

        $menuItems = [];

        foreach ($controllers as $controller) {
            $menuItems[ucfirst($controller)] = [];
        }

        return $menuItems;
    }

    public function newMenuItems()
    {
        $conditionArray = ['menu_is_active' => 1];


        if (!$this->session->get('system_admin')) {
            $this->read_db->table($this->table)->where($conditionArray);
        }

        $registeredMenus = $this->read_db->table($this->table)->get()->getResultArray();
        $menus = array_column($registeredMenus, 'menu_name');

        $formattedMenus = array_map([$this, 'toLower'], $menus);

        $specs = $this->getAllTables();

        $diff = array_diff($specs, $formattedMenus);

        if (($key = array_search('menu', $diff)) !== false) {
            unset($diff[$key]);
        }

        return $diff;
    }

    protected function toLower($string)
    {
        return strtolower($string);
    }


    public function setMenuSessions()
    {
        $menus = $this->getMenuItems(); 
        $newMenuItems = $this->newMenuItems();

        if (!empty($newMenuItems)) {
            $this->upsertMenu($menus);
        }

        $sizeOfMenuItemsByController = count($menus);
        $sizeOfMenuItemsByDatabase = $this->getCountOfMenuItems();

        if ($sizeOfMenuItemsByController !== $sizeOfMenuItemsByDatabase) {
            session()->remove(['user_menu', 'user_priority_menu', 'user_more_menu']);
        }

        if (!session()->has('user_menu')) {
            $userMenuItems = $this->upsertUserMenu();

            $fullUserMenu = elevateArrayElementToKey($userMenuItems, 'menu_derivative_controller');

            $userMenuByPriorityGroups = elevateAssocArrayElementToKey($userMenuItems, 'menu_user_order_priority_item');

            $userPriorityMenu = elevateArrayElementToKey($userMenuByPriorityGroups[1], 'menu_derivative_controller');
            $userMoreMenu = elevateArrayElementToKey($userMenuByPriorityGroups[0], 'menu_derivative_controller');

            $this->session->set('user_menu', $fullUserMenu);

            if (!session()->get('system_admin')) {
                $userPriorityMenuBasedOnPermissions = [];
                $userMoreMenuBasedOnPermissions = [];

                foreach ($userPriorityMenu as $menu => $options) {
                    if (isset(session()->get('role_permissions')[ucfirst($menu)]) && array_key_exists('read', session()->get('role_permissions')[ucfirst($menu)][1])) {
                        $userPriorityMenuBasedOnPermissions[$menu] = $options;
                    }
                }

                foreach ($userMoreMenu as $menu => $options) {
                    if (isset(session()->get('role_permissions')[ucfirst($menu)]) && array_key_exists('read', session()->get('role_permissions')[ucfirst($menu)][1])) {
                        $userMoreMenuBasedOnPermissions[$menu] = $options;
                    }
                }

                if (
                    count($userPriorityMenuBasedOnPermissions) < service("settings")->get("GrantsConfig.maxPriorityMenuItems") - 1 &&
                    count($userMoreMenuBasedOnPermissions) > 0
                ) {
                    $chunkedUserMore = array_chunk($userMoreMenuBasedOnPermissions, service("settings")->get("GrantsConfig.maxPriorityMenuItems") - 1, true);

                    foreach ($chunkedUserMore[0] as $menu => $options) {
                        $userPriorityMenuBasedOnPermissions[$menu] = $options;
                    }

                    $userMoreMenuBasedOnPermissions = array_slice($userMoreMenuBasedOnPermissions, service("settings")->get("GrantsConfig.maxPriorityMenuItems") - 1);
                }

                session()->set('user_priority_menu', $userPriorityMenuBasedOnPermissions);
                session()->set('user_more_menu', $userMoreMenuBasedOnPermissions);
            } else {
                session()->set('user_priority_menu', $userPriorityMenu);
                session()->set('user_more_menu', $userMoreMenu);
            }
        }
    }

    public function navigationItems()
    {

        // $permission = $this->session->role_permissions;
        $this->setMenuSessions();
        $menus = $this->session->get('user_priority_menu');
        $nav = "";
        $menu_icon = '';

        $all_active_menus_obj = $this->read_db->table($this->table)
            ->where('menu_is_active', 1)
            ->get();

        $menu_derivative_controllers = array_column($all_active_menus_obj->getResultArray(), 'menu_derivative_controller');
        $uniqueIdentifierLibrary = new UniqueIdentifierLibrary();
        $unique_identifier = $uniqueIdentifierLibrary->getAccountSystemUniqueIdentifier($this->session->get('user_account_system_id'));

        $userLibrary = new UserLibrary();

        $nav .= view("general/menu_item", ['menu' => service("settings")->get("GrantsConfig.defaultLaunchPage"), 'menu_name' => service("settings")->get("GrantsConfig.defaultLaunchPage"), 'icon' => 'fa fa-home']);

        foreach ($menus as $menu => $items) {
            if (
                $userLibrary->checkRoleHasPermissions($menu, 'read') &&
                in_array(ucfirst($menu), $menu_derivative_controllers) && 
                strtolower($menu) != 'dashboard'
            ) {

                if (
                    !$this->session->get('data_privacy_consented') &&
                    $menu != ucfirst($this->session->get('default_launch_page')) &&
                    !empty($unique_identifier)
                ) {
                    continue;
                }

                $nav .= view("general/menu_item", ['menu' => $menu, 'menu_name' => $items['menu_name'], 'icon' => $menu_icon]);
            }
        }

        if (count($this->session->get('user_more_menu')) > 0) {
            $nav .= '
        <li class="">
            <a href="' . base_url() . 'menu/list">
                <span class="fa fa-plus"></span>
            </a>
        </li>
        ';
        }

        return $nav;
    }


    public function getFavoriteMenuItems()
    {
        $db = db_connect();  // Get the database connection
        $userId = session()->get('user_id');  // Get user id from session

        // Count the number of favorite menu items
        $countOfFavorites = $db->table('menu_user_order')
            ->where(['fk_user_id' => $userId, 'menu_user_order_is_favorite' => 1])
            ->countAllResults();

        // Get the menu items with their names and derivative controllers
        $query = $db->table('menu_user_order')
            ->select(['menu.menu_id', 'menu.menu_name', 'menu.menu_derivative_controller'])
            ->join('menu', 'menu_user_order.fk_menu_id = menu.menu_id')
            ->where(['fk_user_id' => $userId, 'menu_user_order_is_favorite' => 1])
            ->get();

        $items = [];

        if ($query->getNumRows() > 0) {
            $itemResult = $query->getResultArray();

            $itemNamesRaw = array_column($itemResult, 'menu_name');

            // Use the helper to convert item names to phrases in lower case
            $itemNames = array_map(function ($elem) {
                return get_phrase($elem);
            }, $itemNamesRaw);

            $itemControllers = array_column($itemResult, 'menu_derivative_controller');

            $itemControllers = array_map(function ($elem) {
                return strtolower($elem);
            }, $itemNamesRaw);

            $items = array_combine($itemControllers, $itemNames);
        }
        
        $data['item_list'] = $items;
        $data['max_items_reached'] = $countOfFavorites == config(\Config\GrantsConfig::class)->maxCountOfFavoritesMenuItems;

        return $data;
    }

    function createBreadcrumb(){

        $breadcrumb_list = $this->session->has('breadcrumb_list') ? $this->session->breadcrumb_list : [];
        $uri = service("uri");

        if($uri->getSegment(2,'list') == 'list' ){
          $this->session->set('breadcrumb_list',array($uri->getSegment(1,'')));
        }

        if(array_pop($breadcrumb_list) !== $uri->getSegment(1,'') ){
          $breadcrumb_list = $this->session->breadcrumb_list;
          $new = array($uri->getSegment(1,'') );

          if(!in_array($uri->getSegment(1,''),$breadcrumb_list)){
            $breadcrumb_list = array_merge($breadcrumb_list,$new);
          }

          $this->session->set('breadcrumb_list', $breadcrumb_list );
        }
    }

    function checkIfMenuIsActive($menuItem){
        return $this->read_db->table('menu')
            ->where(array('menu_name' => $menuItem, 'menu_is_active' => 0))
            ->get()->getNumRows() > 0;
    }
}