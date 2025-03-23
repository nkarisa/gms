<?php

namespace App\Controllers\Web\Core;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\RedirectResponse;
use App\Libraries\System\AwsParameterStoreLibrary as AwsParameterStore;


class Login extends WebController
{
    use \App\Traits\Core\UserSelfCreation;

    private string $system_name;
    private string $system_title;

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->system_name = $this->settings->get('GrantsConfig.systemName');
        $this->system_title = $this->settings->get('GrantsConfig.systemName');
    }

    /**
     * This method handles the login page and user authentication.
     *
     * @return string|RedirectResponse Returns the login view if user is not authenticated,
     *                                otherwise redirects to the dashboard.
     */
    public function index(): string|RedirectResponse
    {

        // Prepare data for the login view
        $data['system_name'] = $this->system_name;
        $data['system_title'] = $this->system_title;
        $data['maintenance_mode'] = $this->settings->get('GrantsConfig.maintenance_mode');

        // Check if user is already authenticated
        if ($this->session->has('user_is_authenticated')) {

 $grantsLibrary = new \App\Libraries\System\GrantsLibrary();

            if (parse_url(base_url())['host'] == 'localhost' && $this->session->system_admin) {
                $grantsLibrary = new \App\Libraries\System\GrantsLibrary();
                $grantsLibrary->create_missing_system_files_from_json_setup();
            }

            if ($this->session->system_admin) {
                $grantsLibrary->loadConfigurations();
            }

            // Redirect to dashboard if user is authenticated
            if ($this->settings->get("GrantsConfig.maintenance_mode") && !$this->session->system_admin) {
                return view('general/login', $data);
            }

            return redirect()->to('dashboard/list');
        }

        // Return the login view with the prepared data
        return view('general/login', $data);
    }


    /**
     * This method handles the AJAX login request.
     *
     * @return void It does not return anything, but it sends a JSON response to the client.
     */
    public function ajax_login(): ResponseInterface|null
    {
        // Initialize an empty response array
        $response = array();

        // Get the email and password from the POST request
        $email = $_POST["email"];
        $password = $_POST["password"];

        // Add the submitted data to the response array
        $response['submitted_data'] = $this->request->getPost(); // $_POST;

        // Validate the login credentials
        $login_status = $this->validate_login(strtolower(trim($email)), $password);


        // Add the login status to the response array
        $response['login_status'] = $login_status;

        // If the login status is 'success', add an empty redirect URL to the response array
        if ($login_status == 'success') {
            $response['redirect_url'] = '';
            // Send the JSON response to the client
            return $this->response->setJSON($response);
        } 
        return null;
    }

    function validate_login(string $email, string $password = '', bool $is_user_switch = false): string
    {

        // $userModel = new UserModel();
        $user = [];

        // Set the user array
        if (($password != '' && !$is_user_switch) || !$is_user_switch) {
            $password = $this->password_salt($password);
            $user = $this->libs->loadLibrary('user')->getUserInfo(['user_email' => $email, 'user_is_active' => 1, 'user_password' => $password]);
        } else {
            $user = $this->libs->loadLibrary('user')->getUserInfo(['user_email' => $email, 'user_is_active' => 1]);
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
        $userLibrary = $this->libs->loadLibrary('user');
        $languageLibrary = $this->libs->loadLibrary('language');
        $user_id = $user['user_id'];
        $user_language_id = $user['language_id'];

        // Prepare user session data
        $roleIds = array_keys($userLibrary->getUserRoles($user_id));

        $user_session = [
            'user_id' => $user_id,
            'name' => $user['user_firstname'] . ' ' . $user['user_lastname'],
            'user_is_authenticated' => 1,
            'user_locale' => $languageLibrary->languageLocaleById($user_language_id),
            'role_ids' => $roleIds,
            'roles' => array_values($userLibrary->getUserRoles($user_id)),
            'system_admin' => $user['user_is_system_admin'],
            'role_permissions' => $userLibrary->getUserPermissions($roleIds),
            'is_user_switch' => $is_user_switch,
            'departments' => $userLibrary->getUserDepartments($user_id),
            'default_launch_page' => $this->settings->get("GrantsConfig.defaultLaunchPage"),
            'context_definition' => $userLibrary->getUserContextDefinition($user_id),
            'user_account_system_id' => $user['account_system_id'],
            'user_account_system_code' => $user['account_system_code'],
            'hierarchy_offices' => $userLibrary->userHierarchyOffices($user_id),
            'data_privacy_consented' => $userLibrary->dataPrivacyConsented($user_id),
            'role_status' => $userLibrary->actionableRoleStatus(array_keys($userLibrary->userRoleIds($user_id, true))),
            'context_offices' =>$userLibrary->getUserContextOffices($user_id),
        ];


        if ($is_user_switch && !$this->session->has('primary_user_data')) {
            // Session for the primary user
            $this->session->set('primary_user_data', ['user_id' => $this->session->user_id, 'user_name' => $this->session->name]);
        }

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
     * @throws \Exception If there is an error fetching the salt from AWS Parameter Store.
     */
    private function password_salt(string $password): string
    {
        // Initialize the AWS Parameter Store client
       // $store = new AwsParameterStore();

        // Fetch the salt from AWS Parameter Store
       // $salt = $store->getParameterValue('sha256-password-salt');

        // Hash the password with the salt using SHA256 algorithm
        $hashed = hash('sha256', $password . 'compassion_international_mission_2022m_@_SaLT');

        // Return the hashed password
        return $hashed;
    }

    public function switchUser($userId = '')
    {
        // Decode or retrieve user ID from POST data
        $userId = empty($userId) ? $this->request->getPost('user_id') : hash_id($userId, 'decode');

        // Retrieve the target user information
        $user = $this->read_db->table('user')->where('user_id', $userId)->get()->getRowArray();

        // Get current user's email as a fallback
        $currentUser = $this->read_db->table('user')->where('user_id', $this->session->get('user_id'))->get()->getRow();
        $currentUserEmail = $currentUser ? $currentUser->user_email : null;

        $email = $user['user_email'] ?? $currentUserEmail;

        // Validate login with the selected user email
        $loginStatus = $this->validate_login($email, '', true);

        // Redirect based on login status
        return redirect()->to($loginStatus ? site_url('/') : base_url());
    }


    // function checkGlobalLanguagePackState()
    // {
    //     $languageLibrary = new \App\Libraries\Core\LanguageLibrary();

    //     $defaultLocale = service('settings')->get('App.defaultLocale');

    //     // Get all languages
    //     $builder = $this->read_db->table('language');
    //     $builder->select('language_code, language_is_default');
    //     $all_languages = $builder->get()->getResultArray();

    //     $defaultPath = APPPATH . 'language' . DIRECTORY_SEPARATOR . $defaultLocale . DIRECTORY_SEPARATOR . 'Global' . DIRECTORY_SEPARATOR;

    //     if (!file_exists($defaultPath)) {
    //         if (mkdir($defaultPath)) {
    //             foreach ($all_languages as $language) {
    //                 $additonalLanguagePath = APPPATH . 'language' . DIRECTORY_SEPARATOR . $language['language_code'] . DIRECTORY_SEPARATOR . 'Global' . DIRECTORY_SEPARATOR;
    //                 if (!file_exists($additonalLanguagePath)) {
    //                     if (mkdir($additonalLanguagePath)) {
    //                         $languageLibrary->createLanguageFiles($language['language_code'], 'global');
    //                     }
    //                 }
    //             }
    //         }
    //     }
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

    function forgotPassword()
    {
        $data['system_name'] = $this->system_name;
        $data['system_title'] = $this->system_title;

        return view('user/forgot_password', $data);
    }


    function ajax_forgot_password()
    {
        $resp                   = array();
        $resp['status']         = 'false';
        $email                  = $_POST["email"];
        //$reset_account_type     = '';
        //resetting user password here
        $new_password           =   substr(md5(rand(100000, 200000)), 0, 7);

        // Checking credential for user

        $userLibrary = new \App\Libraries\Core\UserLibrary();
        $queryObj = $userLibrary->getUserByEmail($email);

        $user = [];

        if ($queryObj->getNumRows() > 0) {
            $user = $queryObj->getRow();
            $userLibrary->updateUserPasswordByEmail($email, $this->password_salt($new_password));
            $resp['status'] = 'true';

            $emailTemplateLibrary = new \App\Libraries\Core\EmailTemplateLibrary();

            $tags['user'] =  $user->user_firstname . ' ' . $user->user_lastname;
            $tags['email'] = $email;
            $tags['password'] = $new_password;

            $email_subject = get_phrase('password_reset_notification');
            $email_body = file_get_contents(APPPATH . 'resources/email_templates/en/password_reset.txt'); // Template language should be from user session
            $mail_recipients['send_to'] = [$email]; // must be an array

            $emailTemplateLibrary->logEmail($tags, $email_subject, $email_body, $mail_recipients);
            
            $resp['submitted_data'] = $_POST;
        }

        return  $this->response->setJSON($resp);
    }

    function verifyPasswordComplexity(): ResponseInterface
    {
        //Get password inputted and check for password complexity.
        $password = $this->request->getPost('password');
        $un_allowed_password = [];
        
        if($password){
            if (strlen($password) < 8) {
                $un_allowed_password[] = "Password must be more than 8 characters!";
            }
            if (!preg_match("#[0-9]+#", $password)) {
                $un_allowed_password[] = "Password must include at least one number!";
            }
            if (!preg_match('/(?=.*[a-z])(?=.*[A-Z])/', $password)) {
                $un_allowed_password[] = "Password must have a lower and caps letters!";
            }
            if (!preg_match('@[^\w]@', $password)) {
                $un_allowed_password[] = "Password must include at least one a special character!";
            }
        }

        return $this->response->setJSON($un_allowed_password);
    }
}
