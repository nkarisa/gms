<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CancelChequeModel;

class CancelChequeLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $cancelchequeModel;

    function __construct()
    {
        parent::__construct();

        $this->cancelchequeModel = new CancelChequeModel();

        $this->table = 'cancel_cheque';
    }

     /**
     *get_valid_cheques(): Returns the valid cheques.
    * @author Livingstone Onduso: Dated 06-05-2024
    * @access public
    * @return array 
    * @param int $office_bank_id
    */
    public function getValidCheques(int $office_bank_id): array
    {
        $chequeBookLibrary = new ChequeBookLibrary();

        //Get remaining chqs; voucher cancelled chqs and cancelled chqs that were cancelled using cancel cheque feature
        $leaves = $chequeBookLibrary->getRemainingUnusedChequeLeaves($office_bank_id, true);
        $voucher_cancelled_chqs = $this->voucherCancelledCheques($office_bank_id);
        $cancelled_chqs_using_cancel_feature = $this->getCancelledCheques($office_bank_id);

        //Loop and array search the value in the voucher cancelled chq and unset to remove them in the remaing chqs
        foreach ($leaves as $key => $leave) {
            $value = -$leave['cheque_id'];
            //Remove the chqs cancelled in the voucher
            $found_value_in_voucher_cancelled_chqs = array_search($value, array_map(function($elem){
                return abs($elem);
            }, $voucher_cancelled_chqs));

            if ($found_value_in_voucher_cancelled_chqs !== false) {
                unset($leaves[$key]);
            }
            //Remove the chqs cancelled using cancel cheque feature
            $found_value_in_cancelled_chqs_using_cancel_feature = array_search(abs($value), $cancelled_chqs_using_cancel_feature);

            if ($found_value_in_cancelled_chqs_using_cancel_feature !== false) {
                unset($leaves[$key]);
            }
        }

        return $leaves;
    }

     /**
     *voucher_cancelled_cheques(): Returns cancelled chqs in the voucher side.
    * @author Livingstone Onduso: Dated 06-05-2024
    * @access private
    * @return array 
    * @param int $office_bank_id
    */
    private function voucherCancelledCheques(int $office_bank_id):array
    {

        $cancelled_voucher_numbers = [];

        //Get the  active chequebooks
        $chequebk_id= $this->getActiveChequebook($office_bank_id);

        //If the active chq books , get the cancelled chqs in voucher table of the active chequebook.
        $builder = $this->read_db->table("voucher");
        $builder->select('voucher_cheque_number');
        $builder->distinct();
        $builder->where(['fk_cheque_book_id' => $chequebk_id]);
        $builder->like('voucher_cheque_number', '-', 'both');
        $cancelled_voucher_numbers = $builder->get()->getResultArray();

        return array_column($cancelled_voucher_numbers, 'voucher_cheque_number');
      
    }

    /**
   *get_cancelled_cheques(): Returns cancelled chqs .
   * @author Livingstone Onduso: Dated 06-05-2024
   * @access private
   * @return array 
   */
  private function getCancelledCheques(int $office_bank_id): array
  {
      //Get cancelled cheques that are cancelled using cancel cheques feature
      $builder = $this->read_db->table("cancel_cheque");
      $builder->select(['cancel_cheque_number']);
      $builder->join('cheque_book', 'cheque_book.cheque_book_id=cancel_cheque.fk_cheque_book_id');
      $builder->where(['fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1]);
      $cancel_cheque = $builder->get()->getResultArray();

      return array_column($cancel_cheque, 'cancel_cheque_number');
  }

   /**
   *get_active_chequebook():This method gets to pass active chequebook.
   * @author Livingstone Onduso: Dated 06-05-2024
   * @access public
   * @return int 
   * @param int $office_bank_id
   */
  public function getActiveChequebook(int $office_bank_id): int
  {

      $cheque_book_id = 0;
      $builder = $this->read_db->table("cheque_book");
      $builder->select(['cheque_book_id']);
      $builder->where(['cheque_book_is_active' => 1, 'fk_office_bank_id' => $office_bank_id]);
      $result_obj = $builder->get();

      if ($result_obj->getNumRows() > 0) {
          $cheque_book_id = $result_obj->getRow()->cheque_book_id;
      }

      return $cheque_book_id;
  }
   
}