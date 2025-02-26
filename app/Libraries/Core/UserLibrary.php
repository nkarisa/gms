<?php

namespace App\Libraries\Core;

// use App\Libraries\System\AwsAttachmentLibrary;
use App\Libraries\System\GrantsLibrary;
use App\Libraries\Core\UniqueIdentifierLibrary;
use App\Libraries\Core\StatusLibrary;
use App\Models\Core\UserModel;
use CodeIgniter\Database\ResultInterface;

class UserLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
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
     * @throws \Exception If there is an error in executing the database query.
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
     * @throws \Exception If there is an error in executing the database query.
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
            if (service("settings")->get("GrantsConfig.methodToAttachPermissionToRole") == 'both') {
                $role_permissions = $roleAssignedPermissions->getResultObject();
                $role_group_permissions = $roleGroupAssignedPermissions->getResultObject();
            } elseif (service("settings")->get("GrantsConfig.methodToAttachPermissionToRole") == 'direct') {
                $role_permissions = $roleAssignedPermissions->getResultObject();
            } elseif (service("settings")->get("GrantsConfig.methodToAttachPermissionToRole") == 'role_group') {
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
        $default_launch_page = service("settings")->get("GrantsConfig.defaultLaunchPage");
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
     * @throws \Exception If there is an error in executing the database query.
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
        if (!session()->get('system_admin') && service("settings")->get("GrantsConfig.preventUsingGlobalPermissionsByNonAdmins")) {
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

        if (!session()->get('system_admin') && service("settings")->get("GrantsConfig.preventUsingGlobalPermissionsByNonAdmins")) {
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

        $user = $this->getUserInfo(['user_id' => $userId]);
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
     * @param array $searchFields User table search fields.
     * @return array An associative array containing user information.
     *
     * @throws \Exception If there is an error in executing the database query.
     */
    public function getUserInfo(array $searchFields): array
    {
        $searchFieldKeys = array_keys($searchFields);
        $userTableFields = service('grantslib')::call('system.grants.fieldNames', [$this->table]);

        foreach ($searchFieldKeys as $searchFieldKey) {
            if (!in_array($searchFieldKey, $userTableFields)) {
                throw new \Exception('Invalid search field: ' . $searchFieldKey);
            }
        }

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
            'user.user_is_switchable',
            'account_system.account_system_id as account_system_id',
            'account_system.account_system_code as account_system_code',
            'account_system.account_system_name as account_system_name',
        ]);

        // Join the necessary tables
        $builder->join('context_definition', 'context_definition.context_definition_id = user.fk_context_definition_id');
        $builder->join('language', 'language.language_id = user.fk_language_id');
        $builder->join('role', 'role.role_id = user.fk_role_id');
        $builder->join('unique_identifier', 'unique_identifier.unique_identifier_id = user.fk_unique_identifier_id', 'left');
        $builder->join('account_system', 'account_system.account_system_id = user.fk_account_system_id');

        // Apply conditions
        $builder->where($searchFields);

        // Execute the query and return the result
        $userObj = $builder->get();

        // Convert the result to an associative array and return it
        $user = [];
        if ($userObj->getNumRows() > 0) {
            $user = $userObj->getRowArray();
        }

        return $user;
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

        $contextDefinition = $this->getUserContextDefinition($userId);//$this->session->get('context_definition');
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

    public function isFeatureApproveable(string $feature)
    {
        // Get the database connection
        $builder = $this->read_db->table('approve_item');

        // Apply the where condition
        $builder->where(['approve_item_name' => $feature]);

        // Fetch the row and return the value of approve_item_is_active
        $result = $builder->get()->getRow();

        return $result ? $result->approve_item_is_active : null;
    }


    function isStatusActionableByUser($status_id, $feature)
    {
        return in_array($status_id, session()->get('role_status')) || !$this->isFeatureApproveable($feature) ? true : false;
    }


    public function actionableRoleStatus(array $roleIds)
    {
        $userCrudActionableStatus = [];

        // Connect to the database
        $builder = $this->read_db->table('status_role');

        // Select the status_id
        $builder->select(['status.status_id as status_id']);
        $builder->join('status', 'status.status_id = status_role.status_role_status_id');

        if (!$this->session->system_admin) {
            $builder->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id');
            $builder->where(['approval_flow.fk_account_system_id' => $this->session->user_account_system_id]);
        }

        $builder->where(['status_role_is_active' => 1]);
        $builder->whereIn('fk_role_id', $roleIds);

        // Execute the query
        $statusObj = $builder->get();

        if ($statusObj->getNumRows() > 0) {
            $userCrudActionableStatus = array_merge(
                array_column($statusObj->getResultArray(), 'status_id'),
                $this->reinstatingStatus()
            );
        }

        return $userCrudActionableStatus;
    }

    function reinstatingStatus()
    {

        $reinstating_status = [];

        $builder = $this->read_db->table('status');

        if (!$this->session->system_admin) {
            $builder->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
            $builder->where(array('approval_flow.fk_account_system_id' => session()->get('user_account_system_id')));
        }

        $builder->where(array('status_approval_direction' => -1));
        $status_obj = $builder->get();

        if ($status_obj->getNumRows() > 0) {
            $reinstating_status = array_column($status_obj->getResultArray(), 'status_id');
        }

        return $reinstating_status;
    }

    function userRoles($user_id)
    {
        $builder = $this->read_db->table('user');
        $builder->select(['role_id', 'role_name']);
        $builder->join('role', 'role.role_id=user.fk_role_id');
        $builder->where(['user_id' => $user_id]);
        $user_roles_and_ids = $builder->get()->getResultArray();

        $role_ids = array_column($user_roles_and_ids, 'role_id');
        $role_names = array_column($user_roles_and_ids, 'role_name');

        $role_ids_and_names = array_combine($role_ids, $role_names);

        return $role_ids_and_names;

    }

    public function userRoleIds($userId, $mergeWithPrimaryRole = true)
    {
        // Retrieve the user's primary role
        $userPrimaryRole = $this->userRoles($userId);

        // Connect to the database

        $builder = $this->read_db->table('role_user');

        // Build the query
        $builder->select(['role_id', 'role_name']);
        $builder->where(['fk_user_id' => $userId, 'role_user_is_active' => 1]);
        $builder->groupStart();
        $builder->where('role_user_expiry_date IS NULL', null, false);
        $builder->orWhere(['role_user_expiry_date >= ' => date('Y-m-d')]);
        $builder->groupEnd();
        $builder->join('role', 'role.role_id = role_user.fk_role_id');

        // Execute the query
        $roleUserObj = $builder->get();

        $userRoleIds = [];

        if ($roleUserObj->getNumRows() > 0) {
            $userRoleIdsArray = $roleUserObj->getResultArray();
            $roleIds = array_column($userRoleIdsArray, 'role_id');
            $roleNames = array_column($userRoleIdsArray, 'role_name');

            $userRoleIds = array_combine($roleIds, $roleNames);
        }

        // Combine the user role IDs with the primary role
        $combinedUserRoleIds = array_replace($userPrimaryRole, $userRoleIds);

        if (!$mergeWithPrimaryRole) {
            $combinedUserRoleIds = $userRoleIds;
        }

        return $combinedUserRoleIds;
    }


    function checkRoleHasFieldPermission(string $activeController, string $permissionLabel, string $column): bool
    {
        $hasPermission = false;
        $activeController = ucfirst($activeController);

        // Forces checking a field of a detail table
        if (strpos($activeController, "_detail") !== false) {
            $activeController = substr($activeController, 0, -7);
        }

        // Create a query with CodeIgniter 4 query builder
        $builder = $this->read_db->table('permission');
        $builder->join('menu', 'permission.fk_menu_id = menu.menu_id');
        $builder->where('menu.menu_derivative_controller', $activeController);
        $builder->where('permission.permission_field', $column);

        // Run the query
        $isColumnControlled = $builder->get();

        // Check if column is controlled and apply permission logic
        if ($isColumnControlled->getNumRows() > 0) {
            $hasPermission = $this->checkRoleHasPermissions($activeController, $permissionLabel, 2);
        } else {
            $hasPermission = true;
        }

        return $hasPermission;
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

    function computeUserHierarchyOffices($user_context, $user_context_id, $looping_context)
    {

        $contextDefinitionLibrary = new \App\Libraries\Core\ContextDefinitionLibrary();

        $user_context_table = 'context_' . $user_context;
        $user_context_level = $contextDefinitionLibrary->contextDefinitions()[$user_context]['context_definition_level'];
        $contexts = array_keys($contextDefinitionLibrary->contextDefinitions());

        $level_one_context_table = isset($contexts[0]) ? 'context_' . $contexts[0] : null; //center
        $level_two_context_table = isset($contexts[1]) ? 'context_' . $contexts[1] : null; //cluster
        $level_three_context_table = isset($contexts[2]) ? 'context_' . $contexts[2] : null; //cohort
        $level_four_context_table = isset($contexts[3]) ? 'context_' . $contexts[3] : null; // country
        $level_five_context_table = isset($contexts[4]) ? 'context_' . $contexts[4] : null; //region
        $level_six_context_table = isset($contexts[5]) ? 'context_' . $contexts[5] : null; //global

        $builder = $this->read_db->table($user_context_table);

        $builder->select(array('office_id', 'office_name', 'office_is_active'));

        if ($contexts[0] != null && $looping_context == $contexts[0]) { // center

            if ($user_context_level > 5)
                $builder->join($level_five_context_table, $level_five_context_table . '.fk_' . $level_six_context_table . '_id=' . $level_six_context_table . '.' . $level_six_context_table . '_id');
            if ($user_context_level > 4)
                $builder->join($level_four_context_table, $level_four_context_table . '.fk_' . $level_five_context_table . '_id=' . $level_five_context_table . '.' . $level_five_context_table . '_id');
            if ($user_context_level > 3)
                $builder->join($level_three_context_table, $level_three_context_table . '.fk_' . $level_four_context_table . '_id=' . $level_four_context_table . '.' . $level_four_context_table . '_id');
            if ($user_context_level > 2)
                $builder->join($level_two_context_table, $level_two_context_table . '.fk_' . $level_three_context_table . '_id=' . $level_three_context_table . '.' . $level_three_context_table . '_id');
            if ($user_context_level > 1)
                $builder->join($level_one_context_table, $level_one_context_table . '.fk_' . $level_two_context_table . '_id=' . $level_two_context_table . '.' . $level_two_context_table . '_id');

            if ($user_context_level > 1)
                $builder->select(array($level_two_context_table . '.fk_office_id as reporting_office_id'));
        }

        if ($contexts[1] != null && $looping_context == $contexts[1]) { //cluster

            if ($user_context_level > 5)
                $builder->join($level_five_context_table, $level_five_context_table . '.fk_' . $level_six_context_table . '_id=' . $level_six_context_table . '.' . $level_six_context_table . '_id');
            if ($user_context_level > 4)
                $builder->join($level_four_context_table, $level_four_context_table . '.fk_' . $level_five_context_table . '_id=' . $level_five_context_table . '.' . $level_five_context_table . '_id');
            if ($user_context_level > 3)
                $builder->join($level_three_context_table, $level_three_context_table . '.fk_' . $level_four_context_table . '_id=' . $level_four_context_table . '.' . $level_four_context_table . '_id');
            if ($user_context_level > 2)
                $builder->join($level_two_context_table, $level_two_context_table . '.fk_' . $level_three_context_table . '_id=' . $level_three_context_table . '.' . $level_three_context_table . '_id');

            if ($user_context_level > 2)
                $builder->select(array($level_three_context_table . '.fk_office_id as reporting_office_id'));
        }

        if ($contexts[2] != null && $looping_context == $contexts[2]) { //cohort

            if ($user_context_level > 5)
                $builder->join($level_five_context_table, $level_five_context_table . '.fk_' . $level_six_context_table . '_id=' . $level_six_context_table . '.' . $level_six_context_table . '_id');
            if ($user_context_level > 4)
                $builder->join($level_four_context_table, $level_four_context_table . '.fk_' . $level_five_context_table . '_id=' . $level_five_context_table . '.' . $level_five_context_table . '_id');
            if ($user_context_level > 3)
                $builder->join($level_three_context_table, $level_three_context_table . '.fk_' . $level_four_context_table . '_id=' . $level_four_context_table . '.' . $level_four_context_table . '_id');

            if ($user_context_level > 3)
                $builder->select(array($level_four_context_table . '.fk_office_id as reporting_office_id'));
        }

        if ($contexts[3] != null && $looping_context == $contexts[3]) { //country

            if ($user_context_level > 5)
                $builder->join($level_five_context_table, $level_five_context_table . '.fk_' . $level_six_context_table . '_id=' . $level_six_context_table . '.' . $level_six_context_table . '_id');
            if ($user_context_level > 4)
                $builder->join($level_four_context_table, $level_four_context_table . '.fk_' . $level_five_context_table . '_id=' . $level_five_context_table . '.' . $level_five_context_table . '_id');

            if ($user_context_level > 4)
                $builder->select(array($level_five_context_table . '.fk_office_id as reporting_office_id'));
        }

        if ($contexts[4] != null && $looping_context == $contexts[4]) { // region

            if ($user_context_level > 5)
                $builder->join($level_five_context_table, $level_five_context_table . '.fk_' . $level_six_context_table . '_id=' . $level_six_context_table . '.' . $level_six_context_table . '_id');

            if ($user_context_level > 5)
                $builder->select(array($level_six_context_table . '.fk_office_id as reporting_office_id'));
        }

        $builder->join('office', 'office.office_id=context_' . $looping_context . '.fk_office_id');
        $builder->where(array($user_context_table . '_id' => $user_context_id));
        $hierarchy_offices = $builder->get()->getResultArray();

        return $hierarchy_offices;
    }


    function userOfficeGroupAssociations($user_hierarchy_offices)
    {

        $office_group_association = [];

        $user_office_ids = array_column($user_hierarchy_offices, "office_id");

        $builder = $this->read_db->table('office_group_association');
        // Get office group id of the leading office for the group
        if (!empty($user_office_ids)) {
            $builder->select(array('fk_office_group_id'));
            $builder->whereIn("fk_office_id", $user_office_ids);
            $builder->where(array('office_group_association_is_lead' => 1));
            $office_group_ids_array_obj = $builder->get();


            if ($office_group_ids_array_obj->getNumRows() > 0) {

                $office_group_ids_array = $office_group_ids_array_obj->getResultArray();

                $office_group_ids = array_column($office_group_ids_array, 'fk_office_group_id');

                $builder2 = $this->read_db->table('office_group_association');
                $builder2->select(array('office_id', 'office_name', "office_is_active"));
                $builder2->join('office', 'office.office_id=office_group_association.fk_office_id');
                $builder2->whereIn('fk_office_group_id', $office_group_ids);
                $office_group_association_obj = $builder2->get();

                if ($office_group_association_obj->getNumRows() > 0) {
                    $office_group_association = $office_group_association_obj->getResultArray();
                }
            }
        }

        return $office_group_association;
    }

    function officeDirectlyAttachedToUser($user_id)
    {
        $offices = [];

        $builder = $this->read_db->table('office_user');

        $builder->select(array('office_id', 'office_name'));
        $builder->join('office', 'office.office_id=office_user.fk_office_id');
        $builder->where(array('office_user.fk_user_id' => $user_id, 'office_user_is_active' => 1));
        $offices_obj = $builder->get();

        if ($offices_obj->getNumRows() > 0) {
            $offices = $offices_obj->getResultArray();
        }

        return $offices;
    }

    function userHierarchyOffices($user_id, $show_context = false)
    {
        $contextDefinitionLibrary = new \App\Libraries\Core\ContextDefinitionLibrary();

        $user_context_definition = $this->getUserContextDefinition($user_id);


        /**
         * $this->get_user_context_definition($user_id):
         * 
         * Array ( 
         *    [context_definition_id] => 10 
         *    [context_definition_name] => country 
         *    [context_definition_level] => 4 
         *    [context_definition_is_active] => 1 
         * )
         */

        $context_definitions = $contextDefinitionLibrary->contextDefinitions();

        /**
         * $this->grants->context_definitions():
         * 
         * Array ( 
         * [center] => Array ( 
         *    [context_table] => context_center 
         *    [context_user_table] => context_center_user 
         *    [fk] => fk_context_center_id ) 
         * [cluster] => Array ( 
         *    [context_table] => context_cluster 
         *    [context_user_table] => context_cluster_user 
         *    [fk] => fk_context_cluster_id ) 
         * [cohort] => Array ( 
         *    [context_table] => context_cohort 
         *    [context_user_table] => context_cohort_user 
         *    [fk] => fk_context_cohort_id ) 
         * [country] => Array ( 
         *    [context_table] => context_country 
         *    [context_user_table] => context_country_user 
         *    [fk] => fk_context_country_id ) 
         * [region] => Array ( 
         *    [context_table] => context_region 
         *    [context_user_table] => context_region_user 
         *    [fk] => fk_context_region_id ) 
         * [global] => Array ( 
         *    [context_table] => context_global 
         *    [context_user_table] => context_global_user 
         *    [fk] => fk_context_global_id ) )
         */

        $user_context = $user_context_definition['context_definition_name']; // e.g. country
        $user_context_table = $context_definitions[$user_context]['context_table']; // e.g. context_country
        //$user_context_table_user = $context_definitions[$user_context]['context_user_table']; // e.g. context_country_user

        $user_context_level = $context_definitions[$user_context]['context_definition_level']; //e.g. 1 or 2 ....n    $this->db->get_where('context_definition',array('context_definition_name'=>$user_context))->row()->context_definition_level;

        // A user can have multiple context association records e.g. Multiple countries
        $user_context_association = array_column($this->getUserContextAssociation($user_id), $user_context_table . '_id');

        /**
         * $this->get_user_context_association($user_id):
         * 
         * Array ( [0] => Array ( 
         *    [context_country_user_id] => 1 [context_country_id] => 1 [fk_designation_id] => 7 ) 
         *    [context_country_user_id] => 1 [context_country_id] => 2 [fk_designation_id] => 7 ) 
         * )
         */
        $hierachy_context_obj = $contextDefinitionLibrary->getReportingContextLevels($user_context_level);
        /**
         * if 2 i.e. cluster level is passed to $this->get_reporting_context_levels($user_context_level):
         * 
         * Array ( 
         * [0] => Array ( [context_definition_name] => center ) 
         * [1] => Array ( [context_definition_name] => cluster ) )
         *  */
        $hierachy_contexts = array_column($hierachy_context_obj, 'context_definition_name');


        $user_hierarchy_offices = array();
        //$office_ids = array();

        $cnt = 0;

        foreach ($user_context_association as $user_context_id) {
            //$user_context_id can be ids for centers, countries depending on the user context assigned
            foreach ($hierachy_contexts as $hierarchy_context) {
                //$hierarchy_context can be center or cluster or cohort depending on the user context level

                $looped_context_offices = $this->computeUserHierarchyOffices($user_context, $user_context_id, $hierarchy_context);

                if ($show_context) {
                    $user_hierarchy_offices[$hierarchy_context] = $looped_context_offices;
                } else {
                    $user_hierarchy_offices = array_merge($user_hierarchy_offices, $looped_context_offices);
                }

                $cnt++;
            }
        }

        // Merge with Office group Association
        $user_office_group_associations = $this->userOfficeGroupAssociations($user_hierarchy_offices);

        if ($user_office_group_associations && !$show_context) {
            $user_hierarchy_offices = array_merge($user_hierarchy_offices, $user_office_group_associations);
        }

        $office_directly_attached_to_user = $this->officeDirectlyAttachedToUser($user_id);

        if (is_array($office_directly_attached_to_user) && count($office_directly_attached_to_user) > 0) {
            foreach ($office_directly_attached_to_user as $office) {
                array_push($user_hierarchy_offices, $office);
            }
        }

        return array_unique($user_hierarchy_offices, SORT_REGULAR);
    }

    function listTableVisibleColumns(): array
    {
        $columns = array(
            'user_track_number',
            'user_firstname',
            'user_lastname',
            'user_email',
            'user_employment_date',
            'context_definition_name',
            'user_is_system_admin',
            'language_name',
            'user_is_active',
            'status_name',
            'role_name',
            'account_system_name',
            'user_first_time_login'
        );

        return $columns;
    }

    function formatColumnsValues(string $column, mixed $columnValue, array $rowArray, array $dependancyData = []): mixed
    {
        if ($column == 'user_first_time_login') {
            $columnValue = $columnValue == 1 ? get_phrase('yes') : get_phrase('no');
        }

        return $columnValue;
    }


    /**
     * get_user_context_offices
     * 
     * This method returns office ids the user has an association with in his/her context
     * 
     * A user can have multiple offices associated to him or her e.g. A user of context definition of a country
     * can be associated to multiple countries.
     * 
     * @param int $user_id 
     * @return array - Office ids
     */

    function getUserContextOffices(int $user_id)
    {
        $contextDefinitionLibrary = new \App\Libraries\Core\ContextDefinitionLibrary();
        $context_defs = $contextDefinitionLibrary->contextDefinitions();

        // User context
        $user_context_name = strtolower($this->getUserContextDefinition($user_id)['context_definition_name']);

        // User context user table
        $context_table = $context_defs[$user_context_name]['context_table'];
        $context_user_table = $context_defs[$user_context_name]['context_user_table'];

        $builder = $this->read_db->table($context_user_table);

        $builder->select(array('office_name', 'office_id'));
        $builder->join($context_table, $context_table . '.' . $context_table . '_id=' . $context_user_table . '.fk_' . $context_table . '_id');
        $builder->join('office', 'office.office_id=' . $context_table . '.fk_office_id');
        $builder->where(array('fk_user_id' => $user_id));
        $user_context_obj = $builder->get();


        $user_offices = array();

        if ($user_context_obj->getNumRows() > 0) {
            $user_offices = $user_context_obj->getResultArray();
        }

        return $user_offices;
    }

    function userRoleIdsWithExpiryDates($user_id)
    {
        $builder = $this->read_db->table('role_user');
        $builder->select(array('role_id', 'role_name', 'role_user_expiry_date'));
        $builder->where(array('fk_user_id' => $user_id, 'role_user_is_active' => 1));
        $builder->groupStart();
        $builder->where('role_user_expiry_date IS NULL', NULL, FALSE);
        $builder->oRwhere(array('role_user_expiry_date >=' => date('Y-m-d')));
        $builder->groupEnd();
        $builder->join('role', 'role.role_id=role_user.fk_role_id');
        $role_user_obj = $builder->get();

        $user_role_ids = [];

        if ($role_user_obj->getNumRows() > 0) {
            $user_role_ids_array = $role_user_obj->getResultArray();

            foreach ($user_role_ids_array as $role) {
                $user_role_ids[$role['role_id']]['role_name'] = $role['role_name'];
                $user_role_ids[$role['role_id']]['expiry_date'] = $role['role_user_expiry_date'];
            }
        }

        return $user_role_ids;
    }


    function userDesignation($user_id, $context_defination_id)
    {

        switch ($context_defination_id) {
            case 1:
                $context_table = 'context_center_user';
                break;
            case 2:
                $context_table = 'context_cluster_user';
                break;
            case 3:
                $context_table = 'context_cohort_user';
                break;
            case 4:
                $context_table = 'context_country_user';
                break;
            case 5:
                $context_table = 'context_region_user';
                break;
            case 6:
                $context_table = 'context_global_user';
                break;

        }

        $builder = $this->read_db->table($context_table);
        $builder->select(['designation_id', 'designation_name']);
        $builder->join('designation', 'designation.designation_id=' . $context_table . '.fk_designation_id');
        $builder->where(['fk_user_id' => $user_id]);
        $user_designition_and_ids = $builder->get()->getResultArray();

        $designition_ids = array_column($user_designition_and_ids, 'designation_id');
        $designition_names = array_column($user_designition_and_ids, 'designation_name');

        $designitions = array_combine($designition_ids, $designition_names);

        return $designitions;

    }

    function add()
    {
        $response['flag'] = false;
        $response['message'] = get_phrase('user_creation_failed');

        $post = $this->request->getPost()['header'];

        $uniqueIdentifierLibrary = new UniqueIdentifierLibrary();
        $officeLibrary = new OfficeLibrary();

        $this->write_db->transStart();

        $user['user_name'] = sanitize_characters($post['user_name']);
        $user['user_firstname'] = $post['user_firstname'];
        $user['user_lastname'] = $post['user_lastname'];
        $user['user_email'] = strtolower(trim($post['user_email']));
        $user['fk_context_definition_id'] = $post['fk_context_definition_id'];
        $user['user_is_context_manager'] = isset($post['user_is_context_manager']) ? $post['user_is_context_manager'] : 0;
        $user['user_is_system_admin'] = isset($post['user_is_system_admin']) ? $post['user_is_system_admin'] : 0;
        $user['fk_language_id'] = $post['fk_language_id'];
        $user['user_is_active'] = $this->assignUserActiveStatus($post['fk_context_definition_id'], isset($post['fk_account_system_id']) ? $post['fk_account_system_id'] : null); // A user has to be full approved to have the record active. Normal approval flow to be followed.
        $user['md5_migrate'] = 1; //For migrating fro use of php MD5 to complex sha256 with salt
        $user['fk_role_id'] = $post['fk_role_id'];
        $user['user_is_switchable'] = isset($post['user_is_switchable']) ? $post['user_is_switchable'] : 1;
        if ($this->session->system_admin) {
            $user['fk_country_currency_id'] = $post['fk_country_currency_id'];
            $user['fk_account_system_id'] = $post['fk_account_system_id'];
        } else {
            $user['fk_country_currency_id'] = $post['currency_id'];
            $user['fk_account_system_id'] = $officeLibrary->getOfficeAccountSystem($post['fk_user_context_office_id'])['account_system_id']; //$post['account_system_id'];
        }

        $unique_identifier = $uniqueIdentifierLibrary->getAccountSystemUniqueIdentifier($user['fk_account_system_id']);
        $hashed = $this->passwordSalt($post['user_password']);
        $user['user_password'] = $hashed;

        $user_to_insert = $this->mergeWithHistoryFields($this->controller, $user, false);

        $this->write_db->table('user')->insert($user_to_insert);

        $user_id = $this->write_db->insertId();

        // Insert a user in a context table 
        $builder = $this->write_db->table('context_definition');
        $builder->where(array('context_definition_id' => $post['fk_context_definition_id']));
        $context_definition_name = $builder->get()->getRow()->context_definition_name;
        $context_definition_user_table = 'context_' . $context_definition_name . '_user';

        $context[$context_definition_user_table . '_name'] = "Office context for " . $post['user_firstname'] . " " . $post['user_lastname'];
        $context['fk_user_id'] = $user_id;
        $context['fk_context_' . $context_definition_name . '_id'] = $post['fk_user_context_office_id'];
        $context['fk_designation_id'] = $post['designation'];
        $context[$context_definition_user_table . '_is_active'] = 1;

        $context_to_insert = $this->mergeWithHistoryFields($context_definition_user_table, $context, false);

        $this->write_db->table($context_definition_user_table)->insert($context_to_insert);

        // Insert user department
        $department['department_user_name'] = "Department for " . $post['user_firstname'] . " " . $post['user_lastname'];
        $department['fk_user_id'] = $user_id;
        $department['fk_department_id'] = $post['department'];

        $department_to_insert = $this->mergeWithHistoryFields('department_user', $department, false);

        $this->write_db->table('department_user')->insert($department_to_insert);

        $this->write_db->transComplete();

        if ($this->write_db->transStatus() == false) {
            if (isset($unique_identifier['unique_identifier_id'])) {
                $checkIfUniqueIdDublipcates = $uniqueIdentifierLibrary->checkUniqueIdentifierDuplicates($unique_identifier['unique_identifier_id'], $post['user_unique_identifier']);

                if ($checkIfUniqueIdDublipcates['status']) {
                    $response['message'] = get_phrase('duplicate_identifier', 'Duplicate user identification is not allowed');
                } else {
                    $response['message'] = get_phrase('error_occurred');
                }

            }

        } else {
            $response['flag'] = true;
            $response['message'] = get_phrase('user_created_successfully');
        }

        return $this->response->setJSON($response);
    }

    function passwordSalt(string $password): string
    {
        // This construct was built to prevent system admin being forced going to aws while developing on localhost without internet
        // System admins need to have the env file set with a key PASSWORD_SALT and use the value given by the system administrator

        $awsParameterLibrary = new \App\Libraries\System\AwsParameterStoreLibrary();

        $salt = 'none';

        try {
            $salt = $awsParameterLibrary->getParameterValue('sha256-password-salt');
        } catch (\Throwable $th) {
            $salt = env('PASSWORD_SALT');
        }

        $hashed = hash('sha256', $password . $salt);
        return $hashed;
    }


    private function assignUserActiveStatus($user_context_definition_id, $account_system_id = null)
    {
        $uniqueIdentifierLibrary = new UniqueIdentifierLibrary();
        $active_status = 0;
        $account_system_id = $account_system_id == null ? $this->session->user_account_system_id : $account_system_id;

        if ($user_context_definition_id == 1) {
            $account_system_unique_identifier = $uniqueIdentifierLibrary->getAccountSystemUniqueIdentifier($account_system_id);
            if (!empty($account_system_unique_identifier)) {
                $active_status = 1;
            }
        }

        return $active_status;
    }

    function edit($id): \CodeIgniter\HTTP\Response
    {
        $flag = true;
        $flagMessage = '';
        $user_id = hash_id($id, 'decode');

        $uniqueIdentifierLibrary = new UniqueIdentifierLibrary();

        $post = $this->request->getPost()['header'];
        // $user_info = $this->getUserInfo(['user_id' => $user_id]);

        $this->write_db->transStart();

        // Track change history. This line should be placed befire actual editing happens 
        $this->createChangeHistory($post, true, ['user_password']);

        $user['user_firstname'] = $post['user_firstname'];
        $user['user_name'] = sanitize_characters($post['user_name']);
        $user['user_lastname'] = $post['user_lastname'];
        $user['user_email'] = $post['user_email'];
        $user['fk_context_definition_id'] = $post['fk_context_definition_id'];
        $user['user_is_context_manager'] = isset($post['user_is_context_manager']) ? $post['user_is_context_manager'] : 0;
        $user['user_is_system_admin'] = isset($post['user_is_system_admin']) ? $post['user_is_system_admin'] : 0;
        $user['fk_language_id'] = isset($post['fk_language_id']) ? $post['fk_language_id'] : 1;
        $user['user_is_active'] = 1;
        $user['md5_migrate'] = 1; // For migrating fro use of php MD5 to complex sha256 with salt
        $user['fk_role_id'] = $post['primary_role_id'];
        $user['user_is_switchable'] = $post['user_is_switchable'];
        if ($this->session->system_admin) {
            $user['fk_country_currency_id'] = $post['fk_country_currency_id'];

            $user['fk_account_system_id'] = $post['fk_account_system_id'];
        } else {
            $user['fk_country_currency_id'] = $post['currency_id'];

            $user['fk_account_system_id'] = $post['account_system_id'];
        }

        $unique_identifier = $uniqueIdentifierLibrary->validUserUniqueIdentifier($user_id);

        if (isset($unique_identifier['unique_identifier_id']) && $unique_identifier['unique_identifier_id'] > 0) {
            $user['user_employment_date'] = isset($post['user_employment_date']) && $post['user_employment_date'] != '' ? $post['user_employment_date'] : NULL;
            $user['user_unique_identifier'] = isset($post['user_unique_identifier']) && $post['user_unique_identifier'] != '' ? $post['user_unique_identifier'] : NULL;
            $user['fk_unique_identifier_id'] = $unique_identifier['unique_identifier_id'];
        }

        $user_to_insert = $this->mergeWithHistoryFields($this->controller, $user, false, false);

        $builder = $this->write_db->table("user");
        $builder->where(array('user_id' => $user_id));
        $builder->update($user_to_insert);

        // Delete secondary roles if not provided any in the edit form

        if (!isset($post['secondary_role_ids'])) {
            $builder = $this->write_db->table('role_user');
            $builder->where(array('fk_user_id' => $user_id));
            $builder->delete();
        } elseif (count($post['secondary_role_ids']) > 0) {
            $builder = $this->read_db->table('role_user');
            $builder->select(array('fk_role_id'));
            $builder->where(array('fk_user_id' => $user_id));
            $role_user_obj = $builder->get();

            $current_role_ids = [];

            if ($role_user_obj->getNumRows() > 0) {
                $current_role_ids = array_column($role_user_obj->getResultArray(), 'fk_role_id');
            }
            $isEqual = array_diff($post['secondary_role_ids'], $current_role_ids) === array_diff($current_role_ids, $post['secondary_role_ids']);
            // Only update if the current secondary roles do not match the incoming ones
            if (!$isEqual) {
                $builder = $this->write_db->table('role_user');
                $builder->where(array('fk_user_id' => $user_id));
                $builder->delete();

                $cnt = 0;
                foreach ($post['secondary_role_ids'] as $key => $role_id) {
                    $role_user_track = $this->generateItemTrackNumberAndName('role_user');
                    $insert_role_user[$cnt]['role_user_track_number'] = $role_user_track['role_user_track_number'];
                    $insert_role_user[$cnt]['role_user_name'] = $role_user_track['role_user_name'];
                    $insert_role_user[$cnt]['fk_user_id'] = $user_id;
                    $insert_role_user[$cnt]['fk_role_id'] = $role_id;
                    $insert_role_user[$cnt]['role_user_created_date'] = date('Y-m-d');
                    $insert_role_user[$cnt]['role_user_created_by'] = $this->session->user_id;
                    $insert_role_user[$cnt]['role_user_last_modified_by'] = $this->session->user_id;
                    $insert_role_user[$cnt]['role_user_expiry_date'] = $post['expiry_dates'][$key]; // date('Y-m-d', strtotime('+30 days'));
                    $insert_role_user[$cnt]['fk_status_id'] = $this->initialItemStatus('role_user');
                    $insert_role_user[$cnt]['fk_approval_id'] = $this->insertApprovalRecord('role_user');
                    $cnt++;
                }
                $builder = $this->write_db->table('role_user');
                $builder->insertBatch($insert_role_user);
            }
            // }
        }

        // Update a user in a context table 
        $builder = $this->write_db->table('context_definition');
        $builder->where(array('context_definition_id' => $post['fk_context_definition_id']));
        $context_definition_name = $builder->get()->getRow()->context_definition_name;
        $context_definition_user_table = 'context_' . $context_definition_name . '_user';

        $context[$context_definition_user_table . '_name'] = "Office context for " . $post['user_firstname'] . " " . $post['user_lastname'];

        switch ($post['fk_context_definition_id']) {
            case 1:
                $context_table = 'context_center';
                break;
            case 2:
                $context_table = 'context_cluster';
                break;

            case 3:
                $context_table = 'context_cohort';
                break;
            case 4:
                $context_table = 'context_country';
                break;
            case 5:
                $context_table = 'context_region';
                break;
            case 6:
                $context_table = 'context_global';
                break;
        }
        // Check if user is changing the office context
        if ($post['hold_context_definition_id'] != $post['fk_context_definition_id']) {

            switch ($post['hold_context_definition_id']) {
                case 1:
                    $table_to_delete_records_from = 'context_center_user';
                    break;
                case 2:
                    $table_to_delete_records_from = 'context_cluster_user';
                    break;
                case 3:
                    $table_to_delete_records_from = 'context_cohort_user';
                    break;
                case 4:
                    $table_to_delete_records_from = 'context_country_user';
                    break;
                case 5:
                    $table_to_delete_records_from = 'context_region_user';
                    break;
                case 6:
                    $table_to_delete_records_from = 'context_global_user';
                    break;
            }
            //Delete data from the context office where user is moving from
            $builder = $this->write_db->table($table_to_delete_records_from);
            $builder->where(array('fk_user_id' => $user_id));
            $builder->delete();

            //Save record in new context table [Insert a user in a context table] 
            $builder = $this->write_db->table('context_definition');
            $builder->where(array('context_definition_id' => $post['fk_context_definition_id']));
            $context_definition_name = $builder->get()->getRow()->context_definition_name;
            $context_definition_user_table = 'context_' . $context_definition_name . '_user';

            //Get the context ids e.g fk_cluster_id using the office_id [BUG for switching from FCP to cluster and vice versa]
            $builder = $this->write_db->table($context_table);
            $fk_context_column_id = $context_table . '_id';
            $builder->select($fk_context_column_id);
            $builder->where(array('fk_office_id' => $post['fk_user_context_office_id'][0]));
            $fk_context_id_obj = $builder->get();

            $fk_context_id = 0;

            if ($fk_context_id_obj->getNumRows() > 0) {
                $fk_context_id = $fk_context_id_obj->getRow()->$fk_context_column_id;
                $context['fk_context_' . $context_definition_name . '_id'] = $fk_context_id;
                $context[$context_definition_user_table . '_last_modified_by'] = $this->session->user_id;
                $context[$context_definition_user_table . '_name'] = "Office context for " . $post['user_firstname'] . " " . $post['user_lastname'];
                $context['fk_user_id'] = $user_id;
                $context['fk_designation_id'] = $post['designation'];
                $context[$context_definition_user_table . '_is_active'] = 1;

                $context_to_insert = $this->mergeWithHistoryFields($context_definition_user_table, $context, false);

                $this->write_db->table($context_definition_user_table)->insert($context_to_insert);

            }
        } else {

            $column_id = $context_table . '_id';

            if ($post['office_context_changed'] > 0) {
                // Delete all office assignments for the user
                $builder = $this->write_db->table($context_definition_user_table);
                $builder->where(array('fk_user_id' => $user_id));
                $builder->delete();

                foreach (array_unique($post['fk_user_context_office_id']) as $office_id) {
                    $builder = $this->write_db->table('context_' . $context_definition_name);
                    $builder->select(array($column_id));
                    $builder->where(array('fk_office_id' => $office_id));
                    $context_office_id_obj = $builder->get();

                    $context_office_id = 0;

                    if ($context_office_id_obj->getNumRows() > 0) {
                        $context_office_id = $context_office_id_obj->getRow()->$column_id;

                        $context['fk_context_' . $context_definition_name . '_id'] = $context_office_id;
                        $context['fk_designation_id'] = $post['designation'];
                        $context['fk_user_id'] = $user_id;
                        $context[$context_definition_user_table . '_is_active'] = 1;

                        $context_to_insert = $this->mergeWithHistoryFields($context_definition_user_table, $context, false);
                        $this->write_db->table($context_definition_user_table)->insert($context_to_insert);
                    }
                }
            }
        }
        // Update user department
        $department['department_user_name'] = "Department for " . $post['user_firstname'] . " " . $post['user_lastname'];
        $department['fk_department_id'] = $post['department'];

        $department_to_insert = $this->mergeWithHistoryFields('department_user', $department, false);

        $builder = $this->write_db->table('department_user');
        $builder->where(array('fk_user_id' => $user_id));
        $builder->update($department_to_insert);

        $this->write_db->transComplete();

        if ($this->write_db->transStatus() == false) {
            $flagMessage = "Database Error occurred";
        } else {
            $flagMessage = "User record updated";
            $flag = true;
        }

        return $this->response->setJSON(['flag' => $flag, 'message' => $flagMessage]);
    }

    function list($builder, array $columns, string $parentId = null, string $parentTable = null): array
    {
        $users = [];

        $this->dataTableBuilder($builder, $this->controller, $columns);

        $builder->select($columns);
        $builder->join('context_definition', 'context_definition.context_definition_id=user.fk_context_definition_id');
        $builder->join('language', 'language.language_id=user.fk_language_id');
        $builder->join('role', 'role.role_id=user.fk_role_id');
        $builder->join('account_system', 'account_system.account_system_id=user.fk_account_system_id');
        $builder->join('status', 'status.status_id=user.fk_status_id');
        $builder->where(array('user_id <> ' => $this->session->user_id));
        if (!$this->session->system_admin) {
            $user_context = $this->session->context_definition['context_definition_name'];
            if ($user_context == 'cluster') {
                $builder->join('context_center_user', 'context_center_user.fk_user_id=user.user_id');
                $builder->join('context_center', 'context_center.context_center_id=context_center_user.fk_context_center_id');
                $builder->whereIn('context_center.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
            }
            $builder->where(array('user.fk_account_system_id' => $this->session->user_account_system_id));
        }

        $obj = $builder->get();

        if ($obj->getNumRows() > 0) {
            $users = $obj->getResultArray();
        }

        return ['results' => $users];
    }

    function updateApproversList($user_id, $table_name, $item_id, $current_status, $next_status)
    {
        $approvers = [];

        $builder = $this->read_db->table('user');
        $builder->select(array('CONCAT(user_firstname, " ", user_lastname) as fullname', 'role_id', 'role_name'));
        $builder->join('role', 'role.role_id=user.fk_role_id');
        $builder->where(array('user_id' => $user_id));
        $user = $builder->get()->getRow();

        $user_fullname = $user->fullname;
        $user_role_id = $user->role_id;
        $user_role_name = $user->role_name;

        $builder = $this->read_db->table('status');
        $builder->select(array('status_id', 'status_name', 'status_approval_sequence', 'status_approval_direction', 'fk_approval_flow_id as approval_flow_id'));
        $builder->whereIn('status_id', [$current_status, $next_status]);
        $status_obj = $builder->get()->getResultArray();

        $status = [];
        $approval_flow_id = 0;
        foreach ($status_obj as $step) {
            $approval_flow_id = $step['approval_flow_id'];
            if ($step['status_id'] == $current_status) {
                $status['current'] = $step;
            } else {
                $status['next'] = $step;
            }
        }

        $current_status_name = $status['current']['status_name'];
        $current_status_sequence = $status['current']['status_approval_sequence'];
        $current_approval_direction = $status['current']['status_approval_direction'];

        $reinstatement_status_id = 0;

        if ($current_approval_direction == 0) {
            $builder = $this->read_db->table('status');
            $builder->select(array('status_id', 'status_name', 'status_approval_sequence', 'status_approval_direction', 'fk_approval_flow_id as approval_flow_id'));
            $builder->where(['fk_approval_flow_id' => $approval_flow_id]);
            $builder->where(['status_approval_sequence' => $current_status_sequence, 'status_approval_direction' => 1]);
            $alt_status = $builder->get()->getRowArray();

            $reinstatement_status_id = $current_status;
            $current_status = $alt_status['status_id'];
            $current_status_name = $alt_status['status_name'];
            $current_status_sequence = $alt_status['status_approval_sequence'];
            $current_approval_direction = $alt_status['status_approval_direction'];
        }

        $next_status_name = $status['next']['status_name'];
        $next_status_sequence = $status['next']['status_approval_sequence'];
        $next_approval_direction = $status['next']['status_approval_direction'];

        $builder = $this->read_db->table($table_name);
        $builder->where(array($table_name . '_id' => $item_id));
        $existing_approvers = $builder->get()->getRow()->{$table_name . '_approvers'};

        $approvers = $existing_approvers != NULL ? json_decode($existing_approvers): [];

        $new_approver = [
            'user_id' => $user_id,
            'fullname' => $user_fullname,
            'user_role_id' => $user_role_id,
            'user_role_name' => $user_role_name,
            'approval_date' => date('Y-m-d h:i:s'),
            'status_id' => $next_approval_direction == 1 ? $current_status : $next_status,
            'status_name' => $next_approval_direction == 1 ? $current_status_name : $next_status_name,
            'status_sequence' => $next_approval_direction == 1 ? $current_status_sequence : $next_status_sequence,
            'approval_direction' => $next_approval_direction == 1 ? $current_approval_direction : $next_approval_direction,
            'reinstatement_status_id' => $reinstatement_status_id
        ];
        // log_message('error', $approvers);
        if ($existing_approvers == "" || $existing_approvers == "[]" || $existing_approvers == NULL) {
            $approvers = [$new_approver];
        } else {
            array_push($approvers, $new_approver);
        }

        $approvers = json_encode($approvers);

        return $approvers;
    }

    /**
     * email_exists(): check if email exists
     * @author Onduso 
     * @access public 
     * @return int
     */
    function emailExists($email): int
    {

        $builder = $this->read_db->table($this->table);

        $builder->select(['user_email']);
        $builder->where(['user_email' => trim($email)]);
        $email_exists = $builder->countAllResults();

        $is_email_present = 0;

        if ($email_exists > 0) {
            $is_email_present = 1;
        }

        return $is_email_present;
    }

    /**
     * get_user_departments_roles_and_designations(): returns departments based on selected office context e.g. fcp/cluster
     * @author Onduso 
     * @access public 
     * @return array
     * @Dated: 10/8/2023
     * @param int $context_definition_id
     */
    public function getUserDepartmentsRolesAndDesignations(int $user_type, string $table_name, int $countryID): array
    {

        $column_id = $table_name . '_id';
        $column_name = $table_name . '_name';

        $builder = $this->read_db->table($table_name);
        $builder->select([$column_id, $column_name]);
        //Other national offices represented by user_type 5
        if ($user_type == 5) {
            $builder->where(['fk_context_definition_id' => 4]);
        } else {
            $builder->where(['fk_context_definition_id' => $user_type]);
        }

        if ($countryID != 0) {
            $builder->where(['fk_account_system_id' => $countryID]);
        }

        $departments_or_roles_or_designations = $builder->get()->getResultArray();

        //Modify if user Type is Country Admins
        $modify_user_for_admins = [];
        if ($user_type == 4) {
            $modify_user_for_admins[] = $departments_or_roles_or_designations[0];
            $departments_or_roles_or_designations = $modify_user_for_admins;
        }

        //Remove country administrators from dropdown for other national staffs
        if ($user_type == 5) {
            array_shift($departments_or_roles_or_designations);
        }

        $ids = array_column($departments_or_roles_or_designations, $column_id);
        $names = array_column($departments_or_roles_or_designations, $column_name);

        $ids_and_names = array_combine($ids, $names);

        return $ids_and_names;
    }

    /**
     * get_user_activator_ids(): returns activator_user_ids
     * @author Onduso 
     * @access public 
     * @Dated: 16/8/2023
     * @return array
     * @param int int $user_type, int $office_id
     */
    public function getUserActivatorIds(int $user_type, int $office_id, int $country_id): array
    {
        if ($user_type == 1) {
            //Pfs user_ids to activate fcps staffs
            $activator_user_ids = $this->pullActivatorUsersForFcpUsers($office_id);
        } elseif ($user_type == 2 || $user_type == 3 || $user_type == 5) {
            //Country Admnis to activate national office staff
            $activator_user_ids = $this->pullActivatorUsersForNationalStaffs($country_id);
        } elseif ($user_type == 4) {
            $activator_user_ids = $this->pullActivatorUsersForCountryAdministrators();
        }
        return $activator_user_ids;
    }

    /**
     * pull_activator_users_for_fcp_users(): returns activator_user_ids
     * @author Onduso 
     * @access private 
     * @Dated: 16/8/2023
     * @return array
     * @param int $office_id
     */
    private function pullActivatorUsersForFcpUsers(int $office_id): array
    {
        $builder = $this->read_db->table('context_center');
        $builder->select(['fk_context_cluster_id']);
        $builder->where(['fk_office_id' => $office_id]);
        $context_cluster_id = $builder->get()->getRow()->fk_context_cluster_id;

        //get the activator fk_user_id
        $builder = $this->read_db->table('context_cluster_user');
        $builder->select('fk_user_id');
        $builder->where(['fk_context_cluster_id' => $context_cluster_id]);
        $user_activator_id = $builder->get()->getResultArray();

        return $user_activator_id;
    }

    /**
     * pull_activator_users_for_national_staffs(): returns activator_user_ids
     * @author Onduso 
     * @access private 
     * @Dated: 16/8/2023
     * @return array
     * @param int country_id
     */
    private function pullActivatorUsersForNationalStaffs(int $country_id): array
    {
        $builder = $this->read_db->table('user');
        $builder->select(['user_id']);
        $builder->where(['fk_account_system_id' => $country_id, 'user_is_context_manager' => 1, 'fk_context_definition_id' => 4]);
        $user_ids = $builder->get()->getResultArray();

        return $user_ids;
    }


    /**
     * pull_activator_users_for_country_administrators(): returns activator_user_ids
     * @author Onduso 
     * @access private 
     * @Dated: 17/8/2023
     * @return array
     */
    private function pullActivatorUsersForCountryAdministrators(): array
    {
        $builder = $this->read_db->table('user');
        $builder->select(['user_id']);
        $builder->where(['fk_context_definition_id' => 6]);
        $user_ids = $builder->get()->getResultArray();

        return $user_ids;
    }

    function getUserByEmail($email): ResultInterface{
        $builder = $this->read_db->table('user');
        $builder->where(array('user_email' => $email));
        $query = $builder->get();

        return $query;
    }

    function updateUserPasswordByEmail($email, $new_password){
        $builder = $this->write_db->table('user');
        $builder->where('user_email', $email);
        $builder->update(array('user_password' => $new_password, 'user_first_time_login' => 0));
            
    }

    function getLowestOfficeContext()
    {
        $builder = $this->read_db->table('context_definition');
        return $builder->getWhere(array('context_definition_level' => 1))->getRow();
    }

    function getUserFullName($user_id)
    {
      $user = $this->read_db->table('user')
      ->where(array('user_id' => $user_id))->get()->getRow();
  
      return $user->user_firstname . ' ' . $user->user_lastname;
    }
}