<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ChequeInjection extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }


    /**
     *already_injected(): Checks if the cheque has been injected
     * @author Livingstone Onduso: Dated 08-06-2023
     * @access public
     * @return void - echo already_injected string
     * @param int $office_bank_id, $cheque_number
     */
    function alreadyInjected(int $office_bank_id, int $cheque_number): void
    {

        $chequeBookLibrary = new \App\Libraries\Grants\ChequeBookLibrary();
        $injected_chqs = $chequeBookLibrary->injectedChequeExists($office_bank_id, $cheque_number);

        $injected = '';

        if ($injected_chqs==1) {
            $injected = 'already_injected';
        }
        echo $injected;
    }
   
    /**
     *cheque_to_be_injected_exists_in_range(): Finds the cheques in a range of existing cheque books
     * @author Livingstone Onduso: Dated 08-06-2023
     * @access public
     * @return void - echo 1 or 0
     */
    function chequeToBeInjectedExistsInRange(): ResponseInterface
    {
        $post  = $this->request->getPost();
        $office_bank_id = $post['office_bank_id'];
        $cheque_injection_number  = $post['cheque_number'];
        $chequeBookLibrary = new \App\Libraries\Grants\ChequeBookLibrary();
        $resp = $chequeBookLibrary->chequeToBeInjectedExistsInRange($office_bank_id, $cheque_injection_number);

        return $this->response->setJSON($resp);
    }

}
