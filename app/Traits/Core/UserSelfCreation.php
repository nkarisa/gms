<?php 

namespace App\Traits\Core;

use CodeIgniter\HTTP\ResponseInterface;

trait UserSelfCreation {
    function createAccount()
    {
        $accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
        $countries = $accountSystemLibrary->getCountries();

        $data['system_name'] = $this->system_name;
        $data['system_title'] = $this->system_title;
        $data['countries'] = $countries;

        return view('user/create_account', $data);
    }

    /**
     * verify_valid_email(): checks if email an is a correct formated email
     * @author Onduso 
     * @access public 
     * @return void
     */
    function verifyValidEmail(): void
    {

        $email = $this->request->getPost('email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * verify_password_complexity(): validates the if password requirements are met
     * @author Onduso 
     * @access public 
     * @return void
     */

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

    /**
     * email_exists(): check if email exists
     * @author Onduso 
     * @access public 
     * @return void
     */
    function emailExists(): void
    {
        $email = $this->request->getPost('email');
        $userLibrary = new \App\Libraries\Core\UserLibrary();
        $is_email_present = $userLibrary->emailExists($email);

        echo $is_email_present;
    }

    /**
     * get_offices(): return an array of offices like fcp/cluster/region
     * @author Onduso 
     * @access public 
     * @return void
     * @param int $account_system_id, int $context_definition_id
     */
    public function getOfficesByAccountSystemId(int $account_system_id, int $context_definition_id): ResponseInterface
    {
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        return $this->response->setJSON($officeLibrary->getOfficesByAccountSystemId($account_system_id, $context_definition_id));
    }

    /**
     * get_user_departments_roles_and_designations(): returns departments based on selected office context e.g. fcp/cluster
     * @author Onduso 
     * @access public 
     * @return void
     * @param int $context_definition_id
     */
    public function getUserDepartmentsRolesAndDesignations(int $context_definition_id, string $table_name, int $countryID): ResponseInterface
    {
        $userLibrary = new \App\Libraries\Core\UserLibrary();
        $departments_roles_and_designations = $userLibrary->getUserDepartmentsRolesAndDesignations($context_definition_id, $table_name, $countryID);

        return $this->response->setJSON($departments_roles_and_designations);
    }

    /**
     * get_user_activator_ids(): returns array of user_ids
     * @author Onduso 
     * @access public 
     * @Dated: 16/8/2023
     * @return void
     * @param int $user_type,int $office_id, int $country_id
     */
    public function getUserActivatorIds(int $user_type, int $office_id, int $country_id): ResponseInterface
    {
        $userLibrary = new \App\Libraries\Core\UserLibrary();
        return $this->response->setJSON($userLibrary->getUserActivatorIds($user_type, $office_id, $country_id));
    }

    /**
     * get_country_language(): returns language id
     * @author Onduso 
     * @access public 
     * @return void
     * @param int $account_system_id
     */
    public function getCountryLanguage(int $account_system_id): ResponseInterface
    {
        $accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
        $language = $accountSystemLibrary->getCountryLanguage($account_system_id);
        // log_message('error', json_encode($language));
        return $this->response->setJSON($language);
    }

    /**
     * get_country_currency(): returns currency id
     * @author Onduso 
     * @access public 
     * @return void
     * @param int $account_system_id
     */
    public function getCountryCurrency(int $account_system_id): ResponseInterface
    {
        $countryCurrencyLibrary = new \App\Libraries\Grants\CountryCurrencyLibrary();
        return $this->response->setJSON($countryCurrencyLibrary->getCountryCurrencyByAccountSystemId($account_system_id));
    }

    /**
     * save_create_account_data(): save form data to database 
     * @author Onduso 
     * @access public 
     * @Dated: 15/8/2023
     * @return void
     */
    public function saveCreateAccountData(): ResponseInterface
    {
        log_message('error', json_encode($this->request->getPost()));
        $message = "Account Not Created contact the system administration";

        $this->write_db->transBegin();
        //Save in User Table
        $email = strtolower($this->request->getPost('email'));
        $user_name = explode('@', $email)[0];
        $first_name = $this->request->getPost('first_name');
        $surname = $this->request->getPost('surname');
        $user_office = $this->request->getPost('user_office');
        $plain_text_password = $this->request->getPost('password');

        //Hash password
        $hashed_password = $this->password_salt($plain_text_password);
        $user_type = $this->request->getPost('user_type');

        $last_insert = $this->saveDataInUserTable($first_name, $surname, $email, $user_name, $user_type, $hashed_password);

        //Save in Department user Table
        $department_name = 'Department for' . ' ' . $first_name . ' ' . $surname;

        $this->saveDataInDepartmentUser($department_name, $last_insert);

        //Save data in context_user tables
        $designation = $this->request->getPost('user_designation');

        switch ($user_type) {
            case 1:
                $context_data = $this->insertIntoContextUserTable($first_name, $surname, $user_office, $designation, $last_insert, 'context_center', 'context_center_id');
                //Insert Data in context_user table
                $this->write_db->table('context_center_user')->insert( $context_data);
                break;
            case 2:
                $context_data = $this->insertIntoContextUserTable($first_name, $surname, $user_office, $designation, $last_insert, 'context_cluster', 'context_cluster_id');
                //Insert Data in context_user table
                $this->write_db->table('context_cluster_user')->insert($context_data);
                break;
            case 3:
                $context_data = $this->insertIntoContextUserTable($first_name, $surname, $user_office, $designation, $last_insert, 'context_cohort', 'context_cohort_id');
                //Insert Data in context_user table
                $this->write_db->table('context_cohort_user')->insert($context_data);
                break;
            case 4:
                $context_data = $this->insertIntoContextUserTable($first_name, $surname, $user_office, $designation, $last_insert, 'context_country', 'context_country_id');
                $this->write_db->table('context_country_user')->insert($context_data);
                //Insert Data in context_user table
                break;
            case 5:
                $context_data = $this->insertIntoContextUserTable($first_name, $surname, $user_office, $designation, $last_insert, 'context_country', 'context_country_id');
                $this->write_db->table('context_country_user')->insert($context_data);
                //Insert Data in context_user table
                break;
        }

        //Save Data in the user_account_activation table
        $user_activation_name = $first_name . ' ' . $surname;
        $this->saveDataInUserAccountActivation($user_activation_name, $last_insert, $user_office);


        // Commit or rollback if any issue in either user, context related tables and department_user table
        if ($this->write_db->transStatus() == false) {

            $this->write_db->transRollback();

            $message = "Account Not Created contact the system administration";
        } else {

            $this->write_db->transCommit();

            $message = "Account Created System Administrator will activate soon";
        }

        return $this->response->setJSON(['message' => $message]);
    }

     /**
     * save_data_in_user_account_activation(): saves user activation data in user_account_activation; 
     * @author Onduso 
     * @access private 
     * @return array
     * @param string $user_activation_name, int $last_inserted_id, int $user_office_id
     */
    private function saveDataInUserAccountActivation(string $user_activation_name, int $last_inserted_id, int $user_office_id): void
    {

        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $user_office = $officeLibrary->getOfficeName($user_office_id);

        $user_activator_ids['user_account_activation_name'] = $user_activation_name;
        $user_activator_ids['user_account_activation_track_number'] = $this->libs->generateItemTrackNumberAndName('user_account_activation')['user_account_activation_track_number'];
        $user_activator_ids['user_activator_ids'] = $this->request->getPost('user_activator_ids');
        $user_activator_ids['fk_user_id'] = $last_inserted_id;
        $user_activator_ids['user_type'] = $this->request->getPost('user_type');
        $user_activator_ids['user_account_activation_created_date'] = date('Y-m-d');
        $user_activator_ids['user_works_for'] = $user_office;
        
        $this->write_db->table('user_account_activation')->insert(  $user_activator_ids);
    }

     /**
     * insert_into_context_user_table(): saves data in context tables as context_center_user 
     * @author Onduso 
     * @access private 
     * @return array
     * @param string $first_name, string $surname,int $user_office, int $designation, int $last_insert, string $context_table_name, string $context_column_name
     */
    private function insertIntoContextUserTable(string $first_name, string $surname, int $user_office, int $designation, int $last_insert, string $context_table_name, string $context_column_name): array
    {
        $context_name = 'Office context' . 'for ' . $first_name . ' ' . $surname;
        //Get the context_id
        $builder = $this->read_db->table($context_table_name);
        $builder->where(['fk_office_id' => $user_office]);
        $context_center_id = $builder->get()->getRow()->$context_column_name;

        $context_data[$context_table_name . '_user_track_number'] = $this->libs->generateItemTrackNumberAndName($context_column_name . '_user')[$context_column_name . '_user_track_number'];
        $context_data[$context_table_name . '_user_name'] = $context_name;
        $context_data['fk_' . $context_table_name . '_id'] = $context_center_id;
        $context_data['fk_user_id '] = $last_insert;
        $context_data[$context_table_name . '_user_is_active'] = 1;
        $context_data[$context_table_name . '_user_created_by'] = $last_insert;
        $context_data[$context_table_name . '_user_created_date'] = date('Y-m-d');

        $context_data['fk_designation_id'] = $designation;

        return $context_data;
    }

    /**
     * save_data_in_department_user(): saves department user data; 
     * @author Onduso 
     * @access private 
     * @return array
     * @param string $department_name, int $last_inserted_id
     */
    private function saveDataInDepartmentUser(string $department_name, int $last_inserted_id): void
    {

        $department_data['fk_department_id'] = $this->request->getPost('user_department');
        $department_data['department_user_track_number'] = $this->libs->generateItemTrackNumberAndName('department_user')['department_user_track_number'];
        $department_data['department_user_name'] = $department_name;
        $department_data['fk_user_id'] = $last_inserted_id;
        $department_data['department_user_created_date'] = date('Y-m-d');

        $this->write_db->table('department_user')->insert( $department_data);
    }
    /**
     * save_data_in_user_table(): save user data in user table. 
     * @author Onduso 
     * @access private 
     * @return string
     * @dated: 18/08/2023
     * @param string $first_name, string $surname, string $email, string $user_name, int $user_type, string $hashed_password
     */
    private function saveDataInUserTable(string $first_name, string $surname, string $email, string $user_name, int $user_type, string $hashed_password): int
    {
        //5 = other national office staffs e.g health specialist
        //4 =contry admins
        $user_is_context_manager = 0;

        if ($user_type == 4) {
            $user_is_context_manager = 1;
        }

        if ($user_type == 5) {

            $user_type = 4;
        }

        $user_data['user_firstname'] = $first_name;
        $user_data['user_lastname'] = $surname;
        $user_data['user_email'] = $email;
        $user_data['user_name'] = $user_name;
        $user_data['fk_context_definition_id'] = $user_type;
        $user_data['user_password'] = $hashed_password;
        $user_data['user_is_context_manager'] = $user_is_context_manager;
        $user_data['user_is_system_admin'] = 0;
        $user_data['fk_language_id'] = $this->request->getPost('country_language');
        $user_data['fk_country_currency_id'] = $this->request->getPost('country_currency');
        $user_data['user_is_active'] = 0;
        $user_data['fk_role_id'] = $this->request->getPost('user_role');
        $user_data['fk_account_system_id'] = $this->request->getPost('user_country');
        $user_data['user_first_time_login'] = 0;
        $user_data['md5_migrate'] = 1;
        $user_data['user_track_number '] = $this->libs->generateItemTrackNumberAndName('user')['user_track_number'];
        $user_data['user_created_date'] = date('Y-m-d');

        $this->write_db->table('user')->insert($user_data);

        return $this->write_db->insertId();
    }
}