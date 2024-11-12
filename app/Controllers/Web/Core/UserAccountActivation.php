<?php

namespace App\Controllers\Web\Core;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class UserAccountActivation extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    /**
     * activate_new_user_account(): activates newly created account and deletes the very user from user_account_activation.This is done either by admin/pf/super admins
     * @author Onduso 
     * @access public 
     * @return void
     * @Dated: 18/8/2023
     */
    public function activateNewUserAccount(): ResponseInterface
    {

      $user_activation_id = $this->request->getPost('userIdToActivate');
      $userAccountActivationLibrary = new \App\Libraries\Core\UserAccountActivationLibrary();

       return $this->response->setJSON($userAccountActivationLibrary->activateNewUserAccount($user_activation_id));
    }

    /**
     * reject_activating_new_user_account(): deletes the user from user, context user related table and department_user table.
     * @author Onduso 
     * @access public 
     * @return void
     * @Dated: 18/8/2023
     */
    public function rejectActivatingNewUserAccount(): ResponseInterface
    {
        $user_activation_id = $this->request->getPost('rejectedUserId');
        $rejectReason = $this->request->getPost('rejectReason');

        if($rejectReason == ''){
            $rejectReason = get_phrase('user_no_known','Do not know the new user');
        }
      
        $userAccountActivationLibrary = new \App\Libraries\Core\UserAccountActivationLibrary();
        return  $this->response->setJSON($userAccountActivationLibrary->rejectActivatingNewUserAccount($user_activation_id,$rejectReason));
    }
}
