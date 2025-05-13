<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CancelCheque extends WebController
{

    protected $library = null;
    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }


     /**
     *get_active_chequebook():This method gets to pass active chequebook to Ajax on the client.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return void
     */
    public function getActiveChequeBook(): ResponseInterface
    {
        $office_bank_id = $this->request->getPost('office_bank_id');
        
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $voucherTypeLibrary = new \App\Libraries\Grants\VoucherTypeLibrary();

        $active_cheque_books = $this->library->getActiveChequebook($office_bank_id);
        $office = $officeLibrary->getOfficeByOfficeBankId($office_bank_id);
        $voucherTypeLibrary->createMissingVoidHiddenVoucherTypes($office['account_system_id']);

        return $this->response->setJSON(compact('active_cheque_books'));

    }

     /**
     *result():Returns and array of result.
     * @author Livingstone Onduso: Dated 06-05-2024.
     * @access public
     * @return array
     * @param $id
     */
    public function result($id = 0, $parentTable = null)
    {

        $result = parent::result($id, $parentTable);

        if ($this->action == 'singleFormAdd') {
            $result['office_banks'] = $this->library->getBankAccounts();
            $result['cheque_cancel_reason'] = $this->library->getCancelChequeReason();
        }

        return $result;
    }

    /**
     *get_valid_cheques(): Returns the valid cheques.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return void
     * @param int $office_bank_id
     */
    public function getValidCheques(): ResponseInterface
    {
        $office_bank_id = $this->request->getPost('office_bank_id');
        $cheque_numbers = $this->library->getValidCheques($office_bank_id);
        return $this->response->setJSON(compact('cheque_numbers'));
    }

    
     /**
     *save_cancelled_cheques(): Store cancelled cheques in database.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return void
     */
    public function saveCancelledCheques(): ResponseInterface
    {
        $insert_status = 1;

        //Collect Form values using post and they pick specific ones
        $post = $this->request->getPost();
        $officeBankReadBuilder = $this->read_db->table('office_bank');
        $voucherReadBuilder = $this->read_db->table('voucher');
        $canceChequeWriteBuilder = $this->write_db->table('cancel_cheque');
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $chequeInjectionLibrary = new \App\Libraries\Grants\ChequeInjectionLibrary();

        $this->write_db->transStart();

        $cheque_numbers = $post['cancel_cheque_number'];
        $cheque_book_id= $post['fk_cheque_book_id'];
        $office_bank_id= $post['office_bank_id'];

        //Get office_id
        $officeBankReadBuilder->select(['fk_office_id']);
        $officeBankReadBuilder->where(['office_bank_id'=>$office_bank_id]);
        $office_id = $officeBankReadBuilder->get()->getRow()->fk_office_id;

        //Get the voucher_date
        $voucherReadBuilder->select(['voucher_date']);
        $voucherReadBuilder->where(['fk_office_id'=>(float)$office_id]);
        $voucher_date = $voucherReadBuilder->get()->getRowArray();

        $reason_id = $post['fk_item_reason_id'];
        $other_reason = $post['other_reason'];

        //Loop to store the several cheque numbers that you have selected to cancel
        $cnt = 1;
        foreach ($cheque_numbers as $cheque_number) {
            $itemTrackNumberAndName = $this->libs->generateItemTrackNumberAndName('cancel_cheque');

            $data['fk_cheque_book_id'] = $cheque_book_id;
            $data['cancel_cheque_number'] = $cheque_number;
            $data['fk_item_reason_id']=$reason_id;
            $data['other_reason']=$other_reason;
            $data['cancel_cheque_name'] = $itemTrackNumberAndName['cancel_cheque_name'];
            $data['cancel_cheque_track_number'] = $itemTrackNumberAndName['cancel_cheque_track_number'];
            $data['cancel_cheque_created_date'] = date('Y-m-d');
            $data['cancel_cheque_created_by'] = $this->session->user_id;
            $data['fk_status_id'] = $statusLibrary->initialItemStatus('cancel_cheque');

            //Create voucher record with zero amount
            $last_voucher_id = $voucherLibrary->insertZeroAmountVoucher($cheque_number, $cheque_book_id, $office_bank_id, $cnt);
            //Insert Data :cheque number records
            $data['fk_voucher_id'] = $last_voucher_id;
            $canceChequeWriteBuilder->insert( $data);

            if($last_voucher_id > 0){
                // Check if cheque number is an active injection and disable it
                $chequeInjectionLibrary->disableChequeActiveChequeInjection($office_bank_id, $cheque_number);
            }

            $cnt++;
        }
        //Insert Data
        $this->write_db->transComplete();
        if ($this->write_db->transStatus() == false) {
            $insert_status = 0;
        }

        return $this->response->setJSON(compact('insert_status'));

    }
}
