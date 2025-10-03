<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class PayHistory extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function getPayHistoryUsers(){
        $userLibrary = new \App\Libraries\Core\UserLibrary();
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();

        $post = $this->request->getPost();
        $officeId = $post['officeId'];

        $officeAccountSystem = $officeLibrary->getOfficeAccountSystem($officeId);
        $officeAccountSystemId = $officeAccountSystem['account_system_id'];

        $officeUsers = $userLibrary->getActiveOfficeStaff($officeId, $officeAccountSystemId);

        return $this->response->setJSON($officeUsers);
    }

    function getUserLatestPayHistory(){
        $payHistoryLibrary = new \App\Libraries\Grants\PayHistoryLibrary();
        $post = $this->request->getPost();
        $userId = $post['userId'];
        $payHistory = $payHistoryLibrary->getUserLatestPayHistory($userId);
        return $this->response->setJSON([$payHistory]);
    }

    function getLoggedUserOffices(){

        $offices = $this->session->hierarchy_offices;

        $offices = array_filter($offices, fn($office) => $office['office_is_active'] === '1');

        return $this->response->setJSON($offices);
    }

    function getOfficeUsers($officeId){
        $userBuilder = $this->read_db->table('user');

        $userBuilder->select('user_id, CONCAT(user_firstname, " ", user_lastname) as name');
        $userBuilder->join('context_center_user','context_center_user.fk_user_id=user.user_id');
        $userBuilder->join('context_center','context_center.context_center_id=context_center_user.fk_context_center_id');
        $userBuilder->where('context_center.fk_office_id', $officeId);
        $userBuilder->where('user.user_is_active', '1');
        $usersObj = $userBuilder->get();

        $users = [];

        if($usersObj->getNumRows() > 0){
            $users = $usersObj->getResultArray();
        }

        return $this->response->setJSON($users);
    }

    private function officeHasLiabilityBankAccount($officeId){
        return false;
    }

    function getOfficeEarningCategories($officeId){
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $officeAccountSystem = $officeLibrary->getOfficeAccountSystem($officeId);
        $officeAccountSystemId = $officeAccountSystem['account_system_id'];

        $officeHasLiabilityBankAccount = $this->officeHasLiabilityBankAccount($officeId);

        $earningCategoryBuilder = $this->read_db->table('earning_category');
        $earningCategoryBuilder->select('earning_category_id as id, earning_category_name as name');
        $earningCategoryBuilder->where('fk_account_system_id', $officeAccountSystemId);
        $earningCategoryBuilder->where('earning_category_is_recurring', '1');
        
        $officeHasLiabilityBankAccount ? 
            $earningCategoryBuilder->where('earning_category_is_accrued', '1'):
            $earningCategoryBuilder->where('earning_category_is_accrued', '0');

        $earningCategoriesObj = $earningCategoryBuilder->get();

        $earningCategories = [];

        if($earningCategoriesObj->getNumRows() > 0){
            $earningCategories = $earningCategoriesObj->getResultArray();
        }

        return $this->response->setJSON($earningCategories);
    }

    function savePayHistory(){
        $post = $this->request->getPost();

        $payHistoryLibrary = new \App\Libraries\Grants\PayHistoryLibrary();
        $response = $payHistoryLibrary->savePayHistory($post);

        return $this->response->setJSON($response);
    }

    function getPayHistory($payHistoryId){
        $payHistoryLibrary = new \App\Libraries\Grants\PayHistoryLibrary();

        $response = $payHistoryLibrary->getPayHistory(hash_id($payHistoryId,'decode'));

        return $this->response->setJSON($response);
    }
}
