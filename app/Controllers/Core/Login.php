<?php

namespace App\Controllers\Core;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\RedirectResponse;
use App\Models\Core\SettingModel;
use App\Models\Core\UserModel;
use App\Libraries\System\AwsParameterStoreLibrary as AwsParameterStore;
use App\Libraries\Core\UserLibrary;

class Login extends BaseController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    // public function index(string $segment = 'login'){
    //     $this->{$segment}();
    // }

    /**
     * This method handles the login page and user authentication.
     *
     * @return string|RedirectResponse Returns the login view if user is not authenticated,
     *                                otherwise redirects to the dashboard.
     */
    public function index(): string|RedirectResponse
    {
        // Check if user is already authenticated
        if ($this->session->has('user_is_authenticated')) {
            // Redirect to dashboard if user is authenticated
            return redirect()->to('dashboard/list');
        }

        // Initialize the SettingModel
        $settingModel = new SettingModel();

        // Fetch all settings from the database
        $settings = $settingModel->all();

        // Prepare data for the login view
        $data['system_name'] = $settings['system_name'];
        $data['system_title'] = $settings['system_title'];
        $data['maintenance_mode'] = $settings['maintenance_mode'];

        // Return the login view with the prepared data
        return view('login/index', $data);
    }


    /**
     * This method handles the AJAX login request.
     *
     * @return void It does not return anything, but it sends a JSON response to the client.
     */
    public function ajax_login(): ResponseInterface
    {
        // Initialize an empty response array
        $response = array();

        // Get the email and password from the POST request
        $email = $_POST["email"];
        $password = $_POST["password"];

        // Add the submitted data to the response array
        $response['submitted_data'] = $_POST;

        // Validate the login credentials
        $login_status = $this->validate_login(strtolower(trim($email)), $password);

        // Add the login status to the response array
        $response['login_status'] = $login_status;

        // If the login status is 'success', add an empty redirect URL to the response array
        if ($login_status == 'success') {
            $response['redirect_url'] = '';
        }

        // Send the JSON response to the client
        return $this->response->setJSON($response);
    }

    function validate_login(string $email, string $password = '', bool $is_user_switch = false): string
    {

        $userModel = new UserModel();
        $user = [];

        // Set the user array
        if (($password != '' && !$is_user_switch) || !$is_user_switch) {
            $password = $this->password_salt($password);
            $user = $userModel->search(array('user_email' => $email, 'user_is_active' => 1, 'user_password' => $password), true);
        } else {
            $user = $userModel->search(array('user_email' => $email, 'user_is_active' => 1), true);
        }

        // Create user session or invalidate user
        // On maintainance mode, only system admins can login
        if (empty($user)) {
            return 'invalid';
        } else {
            return $this->create_user_session($user, $is_user_switch);
        }
    }

    /**
     * This method creates a user session after successful login.
     *
     * @param array $user An associative array containing user data.
     * @param bool $is_user_switch A flag to check if user is has switched profiles
     * @return string Returns 'success' if user session is created successfully, 'invalid' if user is in maintenance mode and not a system admin.
     */
    private function create_user_session(array $user, bool $is_user_switch): string
    {
        // Load the User Libary
        $userLibrary = new UserLibrary();
        $user_id = $user['user_id'];

        // Prepare user session data
        $roleIds = array_keys($userLibrary->getUserRoles($user_id));

        $user_session = [
            'user_id' => $user_id,
            'name' => $user['user_firstname'] . ' ' . $user['user_lastname'],
            'user_is_authenticated' => 1,
            'role_ids' => $roleIds,
            'roles' => array_values($userLibrary->getUserRoles($user_id)),
            'system_admin' => $user['user_is_system_admin'],
            'role_permissions' => $userLibrary->getUserPermissions($roleIds),
            'is_user_switch' => $is_user_switch,
            'departments' => $userLibrary->getUserDepartments($user_id),
            'default_launch_page' => $this->config->defaultLaunchPage,
            'context_definition' => $userLibrary->getUserContextDefinition($user_id),
            'user_account_system_id' => $user['fk_account_system_id'],
            'hierarchy_offices' => $userLibrary->userHierarchyOffices($user_id),
            'data_privacy_consented' => $userLibrary->dataPrivacyConsented($user_id),
        ];

        // log_message('error', json_encode($userLibrary->getUserPermissions($roleIds)));

        // If user is already authenticated, remove the previous session data
        if ($this->session->has('user_is_authenticated')) {
            $this->session->remove(array_keys($user_session));
        }

        // Set the user authentication status and user session data
        $this->session->set('user_is_authenticated', 1);
        $this->session->set($user_session);

        // If the system is in maintenance mode and the user is not a system admin, log them out
        if (($this->session->maintenance_mode && !$this->session->system_admin)) {
            $this->session->remove(array_keys($user_session));
            return 'invalid';
        }

        // Return success if user session is created successfully
        return 'success';
    }


    /**
     * This method generates a hashed password using a salt fetched from AWS Parameter Store.
     *
     * @param string $password The password to be hashed.
     * @return string The hashed password.
     *
     * @throws Exception If there is an error fetching the salt from AWS Parameter Store.
     */
    private function password_salt(string $password): string
    {
        // Initialize the AWS Parameter Store client
        $store = new AwsParameterStore();

        // Fetch the salt from AWS Parameter Store
        $salt = $store->getParameterValue('sha256-password-salt');

        // Hash the password with the salt using SHA256 algorithm
        $hashed = hash('sha256', $password . $salt);

        // Return the hashed password
        return $hashed;
    }

    // private function update_login_history($user_id, $user_access_count){
    //     $update_data['user_last_login_time'] = date('Y-m-d H:i:s');
    //     $update_data['user_access_count'] = $user_access_count + 1;
    //     $this->write_db->where(array('user_id' => $user_id));
    //     $this->write_db->update('user', $update_data);
    // }

    /**
     * This method handles the user logout process.
     *
     * @return RedirectResponse Redirects the user to the login page after successful logout.
     */
    public function logout(): RedirectResponse
    {
        // Destroy the user session
        $this->session->destroy();

        // Redirect the user to the login page
        return redirect()->to('login/index');
    }
}
