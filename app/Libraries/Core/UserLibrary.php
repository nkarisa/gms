<?php

namespace App\Libraries\Core;

use App\Libraries\Core\GrantsLibrary;
use App\Libraries\Core\UniqueIdentifierLibrary;
use App\Libraries\Core\StatusLibrary;
use App\Models\Core\UserModel;

class UserLibrary extends GrantsLibrary
{

    protected $table;

    protected $userModel;
    function __construct()
    {
        parent::__construct();

        $this->userModel = new UserModel();

        $this->table = 'user';
    }

    /**
     * This function retrieves the primary role of a user from the database.
     *
     * @param int $userId The unique identifier of the user.
     * @return array An associative array containing the role_id and role_name of the user's primary role.
     *
     * @throws Exception If there is an error in executing the database query.
     */
    public function getUserPrimaryRole(int $userId): array|null
    {
        $userPrimaryRole = $this->read_db
            ->table('user')
            ->select('role_id, role_name')
            ->join('role', 'role.role_id=user.fk_role_id')
            ->where(['user.user_id' => $userId])
            ->get()
            ->getRowArray();

        if ($userPrimaryRole === null) {
            throw new \Exception('No role found for the specified user ID.');
        }

        return $userPrimaryRole;
    }

    /**
     * This function retrieves all roles associated with a user, including the primary role.
     *
     * @param int $userId The unique identifier of the user.
     * @return array An associative array containing the role_id and role_name of all roles associated with the user.
     *
     * @throws Exception If there is an error in executing the database query.
     */
    public function getUserRoles($userId)
    {

        // Retrieve the primary role of the user
        $userPrimaryRole = $this->getUserPrimaryRole($userId);

        // Retrieve all roles associated with the user, including expired ones
        $allUserRoles = $this->read_db->table('user')
            ->join('role_user', 'user.user_id=role_user.fk_user_id')
            ->join('role', 'role_user.fk_role_id=role.role_id')
            ->select('role_id, role_name')
            ->where(['user.user_id' => $userId])
            ->groupStart()
            ->where('role_user_expiry_date IS NULL', NULL, FALSE)
            ->orWhere(array('role_user_expiry_date >=' => date('Y-m-d')))
            ->groupEnd()
            ->get()
            ->getResultArray();

        // Extract role_id and role_name from the result array
        $roleIds = array_column($allUserRoles, 'role_id');
        $roleNames = array_column($allUserRoles, 'role_name');

        // Combine role_id and role_name into an associative array
        $roleIdsAndNames = array_combine($roleIds, $roleNames);

        // Add the primary role to the associative array
        $roleIdsAndNames[$userPrimaryRole['role_id']] = $userPrimaryRole['role_name'];

        // Return the final associative array
        return $roleIdsAndNames;
    }

    /**
     * This function retrieves and processes permissions associated with a user's roles.
     *
     * @param array $role_ids An array of role IDs associated with the user.
     * @return array An associative array containing the processed permissions.
     */
    function getUserPermissions($role_ids)
    {
        $role_permission_array = [];

        $role_permissions = [];
        $role_group_permissions = [];

        // Retrieve directly assigned permissions to roles
        $roleAssignedPermissions = $this->permissionDirectlyAssignedToRoles($role_ids);
        // Retrieve permissions assigned through role groups
        $roleGroupAssignedPermissions = $this->permissionDirectlyAssignedThroughRoleGroup($role_ids);

        // If there are any permissions assigned to roles or role groups
        if ($roleAssignedPermissions->getNumRows() > 0 || $roleGroupAssignedPermissions->getNumRows() > 0) {

            // Depending on the configuration, merge permissions from roles and role groups
            if ($this->config->methodToAttachPermissionToRole == 'both') {
                $role_permissions = $roleAssignedPermissions->getResultObject();
                $role_group_permissions = $roleGroupAssignedPermissions->getResultObject();
            } elseif ($this->config->methodToAttachPermissionToRole == 'direct') {
                $role_permissions = $roleAssignedPermissions->getResultObject();
            } elseif ($this->config->methodToAttachPermissionToRole == 'role_group') {
                $role_group_permissions = $roleGroupAssignedPermissions->getResultObject();
            } else {
                $role_permissions = $roleAssignedPermissions->getResultObject();
            }

            // Merge permissions from roles and role groups
            $role_permissions = array_merge($role_permissions, $role_group_permissions);

            // Process each permission
            foreach ($role_permissions as $row) {
                if ($row->permission_type == 1) {
                    // Determine the highest used permission label depth for the current controller
                    $highest_used_permission_label_depth = isset($role_permission_array[$row->menu_derivative_controller][$row->permission_type])
                        ? max(array_values($role_permission_array[$row->menu_derivative_controller][$row->permission_type]))
                        : 1;

                    // If the permission label depth is greater than or equal to the highest used permission label depth, add it to the array
                    if ($row->permission_label_depth >= $highest_used_permission_label_depth) {
                        $role_permission_array[$row->menu_derivative_controller][$row->permission_type][$row->permission_label_name] = $row->permission_label_depth;
                    }
                } elseif ($row->permission_type == 2) {
                    // Add field-specific permissions to the array
                    $role_permission_array[$row->menu_derivative_controller][$row->permission_type][$row->permission_label_name][$row->permission_field] = $row->permission_name;
                }
            }
        }

        // If the default launch page is not in the permission array or does not have 'read' permission, add it
        $default_launch_page = $this->config->defaultLaunchPage;
        if (
            !array_key_exists($default_launch_page, $role_permission_array) ||
            !in_array('read', $role_permission_array)
        ) {
            $role_permission_array[$default_launch_page][1]['read'][] = "show_dashboard";
        }

        // Update the permitted permission labels based on depth
        foreach ($role_permission_array as $perm_controller => $role_permission) {
            foreach ($role_permission[1] as $permission_label => $permission_label_depth) {
                $role_permission_array = $this->updatePermittedPermissionLabelsBasedOnDepth($role_permission_array, $perm_controller, $permission_label);
            }
        }

        // Return the processed permissions
        return $role_permission_array;
    }

    /**
     * This function updates the permitted permission labels based on depth.
     *
     * @param array &$permissions The reference to the permissions array.
     * @param string $activeController The name of the active controller.
     * @param string $permissionLabel The name of the permission label.
     * @param int $permissionType The type of permission (default is 1).
     * @return array The updated permissions array.
     */
    public function updatePermittedPermissionLabelsBasedOnDepth(array &$permissions, string $activeController, string $permissionLabel, int $permissionType = 1): array
    {
        // Get the permission label depth
        $permissionLabelDepth = $this->permissionLabelDepth($permissionLabel);

        // Initialize an empty array to store the updated permissions
        $updatedPermissions = [];

        // Capitalize the active controller
        $activeController = ucfirst($activeController);

        // Iterate over the permissions array
        foreach ($permissions as $controller => $permission) {
            // Capitalize the controller
            $controller = ucfirst($controller);

            // Check if the current controller is the active controller and if the permission type exists
            if (
                $controller === $activeController && array_key_exists($permissionType, $permissions[$controller])
            ) {
                // Add the current permission label to the updated permissions array
                $updatedPermissions[$controller][$permissionType][$permissionLabel] = $permissionLabel . '_' . strtolower($controller);

                // If there are applicable permission labels, add them to the updated permissions array
                if (count($permissionLabelDepth) > 0) {
                    foreach ($permissionLabelDepth as $applicablePermissionLabel) {
                        $updatedPermissions[$controller][$permissionType][$applicablePermissionLabel] = $applicablePermissionLabel . '_' . strtolower($controller);
                    }
                }
            } else {
                // If the current controller is not the active controller, add the permission as it is
                $updatedPermissions[$controller] = $permission;
            }
        }

        // Return the updated permissions array
        return $updatedPermissions;
    }


    /**
     * This function retrieves the applicable permission labels based on the given permission label depth.
     *
     * @param string $permissionLabel The name of the permission label.
     * @return array An array of applicable permission labels, ordered by their depth.
     */
    public function permissionLabelDepth(string $permissionLabel): array
    {
        // Initialize a database builder for the 'permission_label' table
        $builder = $this->read_db->table('permission_label');

        // Select the 'permission_label_name' and 'permission_label_depth' columns
        $builder->select(['permission_label_name', 'permission_label_depth']);

        // Order the results by 'permission_label_depth' in ascending order
        $permissionLabels = $builder->orderBy('permission_label_depth', 'ASC')
            ->get()->getResultArray();

        // Initialize an empty array to store applicable permission labels
        $applicablePermissionLabels = [];

        // Iterate over the permission labels
        foreach ($permissionLabels as $row) {
            // Stop building the array when we meet the argument permission label
            if ($row['permission_label_name'] === $permissionLabel)
                break;

            // Add the current permission label to the applicable permission labels array
            $applicablePermissionLabels[] = $row;
        }

        // Extract the 'permission_label_name' from the applicable permission labels array
        $permissionLabelDepth = array_column($applicablePermissionLabels, 'permission_label_name');

        // If no applicable permission labels were found, retrieve the permission label with depth 1
        if (sizeof($permissionLabelDepth) === 0) {
            $row = $builder->getWhere(['permission_label_depth' => 1])->getRow();
            $permissionLabelDepth = [$row->permission_label_name];
        }

        // Return the applicable permission labels
        return $permissionLabelDepth;
    }

    /**
     * This function retrieves directly assigned permissions to roles.
     *
     * @param array|int $role_ids The unique identifier(s) of the role(s).
     * @return mixed The result of the database query.
     *
     * @throws Exception If there is an error in executing the database query.
     */
    public function permissionDirectlyAssignedToRoles($role_ids)
    {
        // Initialize a database builder for the 'role_permission' table
        $builder = $this->read_db->table('role_permission');

        // Select the required columns from the joined tables
        $builder->select([
            'menu.menu_derivative_controller',
            'permission.permission_type',
            'permission_label.permission_label_name',
            'permission.permission_field',
            'permission.permission_name',
            'permission_label.permission_label_depth'
        ]);

        // Join the necessary tables
        $builder->join('permission', 'permission.permission_id = role_permission.fk_permission_id');
        $builder->join('permission_label', 'permission_label.permission_label_id = permission.fk_permission_label_id');
        $builder->join('menu', 'menu.menu_id = permission.fk_menu_id');

        // Apply conditions based on the configuration and user role
        if (!session()->get('system_admin') && $this->config->preventUsingGlobalPermissionsByNonAdmins) {
            $builder->where('permission.permission_is_global', 0);
        }

        // Handle single or multiple role IDs
        if (is_array($role_ids)) {
            $builder->whereIn('role_permission.fk_role_id', $role_ids);
        } else {
            $builder->where('role_permission.fk_role_id', $role_ids);
        }

        // Apply additional conditions
        $builder->where([
            'role_permission.role_permission_is_active' => 1,
            'permission.permission_is_active' => 1
        ]);

        // Execute the query and return the result
        $query = $builder->get();
        return $query;
    }

    /**
     * This function retrieves directly assigned permissions to roles through role groups.
     *
     * @param array|int $role_ids The unique identifier(s) of the role(s).
     * @return mixed The result of the database query.
     *
     * @throws Exception If there is an error in executing the database query.
     */
    public function permissionDirectlyAssignedThroughRoleGroup($role_ids)
    {
        // Initialize a database builder for the 'permission_template' table
        $builder = $this->read_db->table('permission_template');

        // Select the required columns from the joined tables
        $builder->select([
            'menu.menu_derivative_controller',
            'permission.permission_type',
            'permission_label.permission_label_name',
            'permission.permission_field',
            'permission.permission_name',
            'permission_label.permission_label_depth'
        ]);

        // Join the necessary tables
        $builder->join('permission', 'permission.permission_id = permission_template.fk_permission_id');
        $builder->join('role_group', 'role_group.role_group_id = permission_template.fk_role_group_id');
        $builder->join('role_group_association', 'role_group_association.fk_role_group_id = role_group.role_group_id');
        $builder->join('permission_label', 'permission_label.permission_label_id = permission.fk_permission_label_id');
        $builder->join('menu', 'menu.menu_id = permission.fk_menu_id');

        // Apply conditions based on the configuration and user role
        $builder->where('role_group_association.role_group_association_is_active', 1);

        if (!session()->get('system_admin') && $this->config->preventUsingGlobalPermissionsByNonAdmins) {
            $builder->where('permission.permission_is_global', 0);
        }

        // Handle single or multiple role IDs
        if (is_array($role_ids)) {
            $builder->whereIn('role_group_association.fk_role_id', $role_ids);
        } else {
            $builder->where('role_group_association.fk_role_id', $role_ids);
        }

        // Apply additional conditions
        $builder->where([
            'role_group.role_group_is_active' => 1,
            'permission.permission_is_active' => 1,
            'permission_template.permission_template_is_active' => 1
        ]);

        // Execute the query and return the result
        $query = $builder->get();
        return $query;
    }


    /**
     * Checks if the user has given consent for data privacy.
     *
     * @param int $userId The unique identifier of the user.
     * @param bool $isUserSwitch Indicates if the user is switching.
     * @return bool Returns true if the user has consented, false otherwise.
     */
    public function dataPrivacyConsented($userId, $isUserSwitch = false)
    {
        $statusLibrary = new StatusLibrary();
        $uniqueIdentifierLibrary = new UniqueIdentifierLibrary();
        $userHasConsented = false;

        $user = $this->getUserInfo($userId);
        $userIsFullyApproved = $statusLibrary->isStatusIdMax('user', $userId);

        $consentDate = $user['user_personal_data_consent_date'];
        $uniqueIdentifier = empty($uniqueIdentifierLibrary->getAccountSystemUniqueIdentifier($user['account_system_id']));
        $contextDefinitionId = $user['context_definition_id'];

        // Check if the user has consented, fully approved, not using unique identifier, or is switching
        if ((!is_null($consentDate) && !$uniqueIdentifier && $userIsFullyApproved) || $contextDefinitionId != 1 || $isUserSwitch) {
            $userHasConsented = true;
        }

        return $userHasConsented;
    }

    /**
     * Retrieves user information based on the given user ID.
     *
     * @param int $userId The unique identifier of the user.
     * @return array An associative array containing user information.
     *
     * @throws Exception If there is an error in executing the database query.
     */
    public function getUserInfo(int $userId): array
    {
        // Initialize a database builder for the 'user' table
        $builder = $this->read_db->table($this->table);

        // Select the required columns from the joined tables
        $builder->select([
            'user.user_id',
            'user.user_firstname',
            'user.user_lastname',
            'user.user_name',
            "CONCAT(user.user_firstname, ' ', user.user_lastname) as fullname",
            'user.user_email',
            'user.user_is_context_manager',
            'user.user_is_system_admin',
            'user.user_is_active',
            'context_definition.context_definition_name',
            'context_definition.context_definition_id',
            'user.user_password',
            'language.language_id',
            'language.language_name',
            'role.role_id',
            'role.role_name',
            'user.fk_account_system_id as account_system_id',
            'user.fk_country_currency_id as country_currency_id',
            'user.fk_status_id as status_id',
            'user.user_employment_date',
            'user.user_unique_identifier',
            'unique_identifier.unique_identifier_id',
            'unique_identifier.unique_identifier_name',
            'user.user_personal_data_consent_date',
            'user.user_is_switchable'
        ]);

        // Join the necessary tables
        $builder->join('context_definition', 'context_definition.context_definition_id = user.fk_context_definition_id');
        $builder->join('language', 'language.language_id = user.fk_language_id');
        $builder->join('role', 'role.role_id = user.fk_role_id');
        $builder->join('unique_identifier', 'unique_identifier.unique_identifier_id = user.fk_unique_identifier_id', 'left');

        // Apply conditions
        $builder->where('user.user_id', $userId);

        // Execute the query and return the result
        $query = $builder->get();

        // Convert the result to an associative array and return it
        return $query->getRowArray();
    }

    /**
     * Retrieves the context associations of a user.
     *
     * @param int $userId The unique identifier of the user.
     * @return array An array of context associations.
     *
     * @throws Exception If there is an error in executing the database query.
     */
    public function getUserContextAssociation(int $userId): array
    {
        $session = session();
        $contextDefinition = $session->get('context_definition');
        $contextTable = 'context_' . strtolower($contextDefinition['context_definition_name']);
        $contextUsersTable = 'context_' . strtolower($contextDefinition['context_definition_name']) . '_user';
        $contextUsersTableId = $this->primaryKeyField($contextUsersTable);

        $builder = $this->read_db->table($contextUsersTable);
        $builder->select([
            $contextUsersTableId,
            "{$contextTable}_id",
            'fk_designation_id'
        ]);
        $builder->join($contextTable, "{$contextTable}.{$contextTable}_id = {$contextUsersTable}.fk_{$contextTable}_id");
        $builder->where([
            'fk_user_id' => $userId,
            "{$contextUsersTable}_is_active" => 1
        ]);

        $query = $builder->get();
        $associationsArray = $query->getNumRows() > 0 ? $query->getResultArray() : [];

        return $associationsArray;
    }

    /**
     * Checks if the user has the required permissions for a specific controller and permission label.
     *
     * @param string $activeController The name of the active controller.
     * @param string $permissionLabel The name of the permission label.
     * @param int $permissionType The type of permission (default is 1).
     * @return bool Returns true if the user has the required permission, false otherwise.
     */
    public function checkRoleHasPermissions(string $activeController, string $permissionLabel, int $permissionType = 1): bool
    {
        $hasPermission = false;
        $uniqueIdentifierLibrary = new UniqueIdentifierLibrary();

        $activeController = ucfirst($activeController);
        $permission = session()->get('role_permissions');
        $userId = session()->get('user_id');
        $userAccountSystemId = session()->get('user_account_system_id');
        $defaultLaunchPage = session()->get('default_launch_page');
        $isUserSwitch = session()->get('is_user_switch');
        $departments = session()->get('departments');
        $systemAdmin = session()->get('system_admin');

        $getUserContextAssociation = $this->getUserContextAssociation($userId);
        $dataPrivacyConsented = $this->dataPrivacyConsented($userId);
        $uniqueIdentifier = $uniqueIdentifierLibrary->getAccountSystemUniqueIdentifier($userAccountSystemId);

        // Check if the user has the required permission based on the conditions
        if (
            (is_array($permission) && array_key_exists($activeController, $permission)
                && array_key_exists($permissionType, $permission[$activeController])
                && array_key_exists($permissionLabel, $permission[$activeController][$permissionType])
                && count($getUserContextAssociation) > 0
                && ($dataPrivacyConsented || (!$dataPrivacyConsented && ($this->controller === $defaultLaunchPage || empty($uniqueIdentifier) || $isUserSwitch)))
                && count($departments) > 0)
            || $systemAdmin
            || $activeController === 'Menu'
        ) {
            $hasPermission = true;
        }

        return $hasPermission;
    }

    /**
     * Retrieves the department IDs associated with a user.
     *
     * @param int $userId The unique identifier of the user.
     * @return array An array of department IDs associated with the user.
     */
    public function getUserDepartments(int $userId): array
    {
        // Initialize a database builder for the 'department_user' table
        $builder = $this->read_db->table('department_user');

        // Select the 'fk_department_id' column
        $builder->select('fk_department_id');

        // Retrieve the department IDs associated with the user
        $userDepartment = $builder->getWhere(['fk_user_id' => $userId]);

        // Initialize an empty array to store the department IDs
        $departmentIds = [];

        // If there are department IDs associated with the user, extract them
        if ($userDepartment->getNumRows() > 0) {
            $departmentIds = array_column($userDepartment->getResultArray(), 'fk_department_id');
        }

        // Return the array of department IDs
        return $departmentIds;
    }
}