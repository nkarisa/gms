<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\MenuModel;
use App\Models\Core\PermissionModel;

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
            ->where('menu_derivative_controller', $this->config->defaultLaunchPage)
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
                } elseif ($order > $this->config->maxPriorityMenuItems - 1) {
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

//     public function upsert_menu($menus)
// {
//     $permissionModel = new PermissionModel();

//     foreach ($menus as $menu => $menuItems) {
//         $data = [
//             'menu_name' => $menu,
//             'menu_derivative_controller' => $menu,
//         ];

//         $countOfActiveMenus = $this->read_db->table($this->table)
//         ->where('menu_derivative_controller', $menu)
//         ->countAllResults();

//         if ($countOfActiveMenus == 0) {
//             $this->menuModel->insert((object)$data);

//             $permissionData = [
//                 'menu_id' => $this->menuModel->getInsertID(),
//                 'table_name' => $menu,
//             ];

//             $permissionModel->insert((object)$permissionData);

//             $grantsModel->insert_missing_approveable_item(strtolower($menu));
//             $grantsModel->mandatory_fields(strtolower($menu));
//             $grantsModel->insert_status_if_missing(strtolower($menu));
//             $grants->create_resource_upload_directory_structure();
//         } else {
//             $db->table('menu')->where('menu_derivative_controller', $menu)->update($data);
//         }
//     }

//     // Array diff
//     $arrMenu = array_column($db->table('menu')->get()->getResultArray(), 'menu_derivative_controller');
//     $removedControllers = array_diff($arrMenu, array_keys($menus));

//     if (count($removedControllers) > 0) {
//         foreach ($removedControllers as $removedController) {
//             $db->transStart();

//             $removedMenu = $db->table('menu')->where('menu_derivative_controller', $removedController)->get()->getRow();

//             if ($removedMenu) {
//                 $allMenuPermissions = $db->table('permission')->where('fk_menu_id', $removedMenu->menu_id)->get();

//                 if ($allMenuPermissions->getNumRows() > 0) {
//                     foreach ($allMenuPermissions->getResult() as $permission) {
//                         $db->table('role_permission')->where('fk_permission_id', $permission->permission_id)->delete();
//                     }
//                 }

//                 // Remove all permissions
//                 $db->table('permission')->where('fk_menu_id', $removedMenu->menu_id)->delete();

//                 // Delete the menu
//                 $db->table('menu')->where('menu_derivative_controller', $removedController)->delete();
//             }

//             $db->transComplete();
//         }
//     }
// }

}