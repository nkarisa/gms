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
     *getValidCheques(): Returns the valid cheques.
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
            $found_value_in_voucher_cancelled_cheques = in_array($value, array_map(function ($elem) {
                return abs($elem);
            }, $voucher_cancelled_chqs));

            if ($found_value_in_voucher_cancelled_cheques !== false) {
                unset($leaves[$key]);
            }
            //Remove the chqs cancelled using cancel cheque feature
            $found_value_in_cancelled_chqs_using_cancel_feature = in_array(abs($value), $cancelled_chqs_using_cancel_feature);

            if ($found_value_in_cancelled_chqs_using_cancel_feature !== false) {
                unset($leaves[$key]);
            }
        }

        return $leaves;
    }

     /**
     *voucherCancelledCheques(): Returns cancelled cheques in the voucher side.
    * @author Livingstone Onduso: Dated 06-05-2024
    * @access private
    * @return array 
    * @param int $office_bank_id
    */
    private function voucherCancelledCheques(int $office_bank_id):array
    {

        $cancelled_voucher_numbers = [];

        //Get the  active chequebooks
        $chequebook_id= $this->getActiveChequebook($office_bank_id);

        //If the active chq books , get the cancelled chqs in voucher table of the active chequebook.
        $builder = $this->read_db->table("voucher");
        $builder->select('voucher_cheque_number');
        $builder->distinct();
        $builder->where(['fk_cheque_book_id' => $chequebook_id]);
        $builder->like('voucher_cheque_number', '-', 'both');
        $cancelled_voucher_numbers = $builder->get()->getResultArray();

        return array_column($cancelled_voucher_numbers, 'voucher_cheque_number');
      
    }

    /**
   *getCancelledCheques(): Returns cancelled cheques .
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
   *getActiveChequebook():This method gets to pass active chequebook.
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

    public function lookup_tables()
    {
        return array('cheque_book');
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

//    /**
//     *get_active_chequebook():This method gets to pass active chequebook.
//     * @author Livingstone Onduso: Dated 06-05-2024
//     * @access public
//     * @return int
//     * @param int $office_bank_id
//     */
//    public function get_active_chequebook(int $office_bank_id): int
//    {
//
//        $cheque_book_id = 0;
//
//        $this->read_db->select(['cheque_book_id']);
//
//        $this->read_db->where(['cheque_book_is_active' => 1, 'fk_office_bank_id' => $office_bank_id]);
//
//        $result_obj = $this->read_db->get('cheque_book');
//
//        if ($result_obj->num_rows() > 0) {
//
//            $cheque_book_id = $result_obj->row()->cheque_book_id;
//        }
//
//        return $cheque_book_id;
//    }

    // public function save_cancelled_cheques(array $post_arr):int
    // {
    //     $insert_status = 1;

    //     $batch_of_data = [];

    //     $post = $post_arr;

    //     $this->write_db->trans_start();

    //     $cheque_numbers = $post['cancel_cheque_number'];

    //     foreach ($cheque_numbers as $cheque_number) {

    //         //log_message('error',$cheque_number);
    //         $data['fk_cheque_book_id'] = $post['fk_cheque_book_id'];

    //         $data['cancel_cheque_number'] = $cheque_number;

    //         $data['cancel_cheque_name'] = $this->grants_model->generate_item_track_number_and_name('cancel_cheque')['cancel_cheque_name'];

    //         $track = $this->grants_model->generate_item_track_number_and_name('cancel_cheque');

    //         $data['cancel_cheque_track_number'] = $track['cancel_cheque_track_number'];

    //         $data['cancel_cheque_created_date'] = date('Y-m-d');

    //         $data['cancel_cheque_created_by'] = $this->session->user_id;

    //         $data['fk_status_id'] = $this->grants_model->initial_item_status('cancel_cheque');

    //         $batch_of_data[] = $data;

    //     }
    //     //Insert Data
    //     $this->write_db->insert_batch('cancel_cheque', $batch_of_data);

    //     $this->write_db->trans_complete();

    //     if ($this->write_db->trans_status() == false) {
    //         $insert_status = 0;
    //     }

    //     return $insert_status;

    // }

    /**
     *get_cancelled_cheques(): Returns cancelled chqs .
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access private
     * @return array
     */
    private function get_cancelled_cheques(int $office_bank_id): array
    {

        //Get cancelled cheques that are cancelled using cancel cheques feature
        $this->read_db->select(['cancel_cheque_number']);

        $this->read_db->join('cheque_book', 'cheque_book.cheque_book_id=cancel_cheque.fk_cheque_book_id');

        $this->read_db->where(['fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1]);

        $cancel_cheque = $this->read_db->get('cancel_cheque')->result_array();

        return array_column($cancel_cheque, 'cancel_cheque_number');
    }

    /**
     *getCancelChequeReason(): Returns returns cancel chq reasons.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return array
     */
    public function getCancelChequeReason():array
    {
//        $this->read_db->select(['item_reason_id', 'item_reason_name']);
//        $this->read_db->where(['fk_approve_item_id' => 144, 'item_reason_is_active'=>1]);
//        $result = $this->read_db->get('item_reason')->result_array();

        $builder = $this->read_db->table('item_reason');
        $builder->select(['item_reason_id', 'item_reason_name']);
        $builder->where(['fk_approve_item_id' => 144, 'item_reason_is_active'=>1]);
        $result = $builder->get()->getResultArray();

        $reason_ids=array_column($result,'item_reason_id');

        $reason_names=array_column($result,'item_reason_name');

        return array_combine($reason_ids, $reason_names);
    }

    /**
     *getChequeBookRange(): Returns the range of cheques as an arrau.
     * @param int $cancelled_cheques_id
     *@return array
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     */
    public function getChequeBookRange(int $cancelled_cheques_id): array
    {

        //Returns the book_serial and count of leaves
//        $this->read_db->select(['cheque_book_start_serial_number', 'cheque_book_count_of_leaves']);
//        $this->read_db->join('cancel_cheque', 'cancel_cheque.fk_cheque_book_id=cheque_book.cheque_book_id');
//        $this->read_db->where(['cancel_cheque_id' => $cancelled_cheques_id]);
//        $result = $this->read_db->get('cheque_book')->result_array();



        $builder = $this->read_db->table('cheque_book');
        $builder->select(['cheque_book_start_serial_number', 'cheque_book_count_of_leaves']);
        $builder->join('cancel_cheque', 'cancel_cheque.fk_cheque_book_id=cheque_book.cheque_book_id');
        $builder->where(['cancel_cheque_id' => $cancelled_cheques_id]);
        $result = $builder->get()->getResultArray();

        return $result;
    }

    /**
     *get_valid_cheques(): Returns the valid cheques.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return array
     * @param int $office_bank_id
     */
    public function get_valid_cheques(int $office_bank_id): array
    {
        $chequeBookLibrary = new ChequeBookLibrary();

        //Get remaining chqs; voucher cancelled chqs and cancelled chqs that were cancelled using cancel cheque feature
        $leaves = $chequeBookLibrary->getRemainingUnusedChequeLeaves($office_bank_id, true);

        $voucherCancelledCheques = $this->voucherCancelledCheques($office_bank_id);
        // log_message('error', json_encode($voucher_cancelled_cheques ));

        $cancelled_chqs_using_cancel_feature = $this->get_cancelled_cheques($office_bank_id);

        //Loop and array search the value in the voucher cancelled chq and unset to remove them in the remaing chqs
        foreach ($leaves as $key => $leave) {

            $value = -$leave['cheque_id'];
            //Remove the chqs cancelled in the voucher
            $found_value_in_voucher_cancelled_chqs = array_search($value, array_map(function($elem){
                if(is_int($elem)){
                    return abs($elem);
                }
                else{
                    return $elem;
                }
//                return abs($elem);
            }, $voucherCancelledCheques));

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
    private function voucher_cancelled_cheques(int $office_bank_id):array
    {

        $cancelled_voucher_numbers = [];

        //Get the  active chequebooks
        $cheque_book_id= $this->getActiveChequebook($office_bank_id);

        //If the active chq books , get the cancelled chqs in voucher table of the active chequebook.
        $this->read_db->select('voucher_cheque_number');

        $this->read_db->distinct();

        $this->read_db->where(['fk_cheque_book_id' => $cheque_book_id]);

        $this->read_db->like('voucher_cheque_number', '-', 'both');

        $cancelled_voucher_numbers = $this->read_db->get('voucher')->result_array();

        return array_column($cancelled_voucher_numbers, 'voucher_cheque_number');

    }

    public function get_bank_accounts(): array
    {

        //User hierachy offices
        $user_office_ids = $this->user_model->user_hierarchy_offices($this->session->user_id);

        $office_ids = array_column($user_office_ids, 'office_id');

        //Get the bank accounts
        $this->read_db->select(['office_bank_id', 'office_bank_name']);

        $this->read_db->where_in('fk_office_id', $office_ids);

        $office_banks = $this->read_db->get('office_bank')->result_array();

        //bank accounts ids and bank names
        $bank_ids = array_column($office_banks, 'office_bank_id');

        $bank_names = array_column($office_banks, 'office_bank_name');

        $bank_ids_and_names = array_combine($bank_ids, $bank_names);

        return $bank_ids_and_names;
    }
//    /**
//     *getActiveChequebook():This method gets to pass active chequebook.
//     * @author Livingstone Onduso: Dated 06-05-2024
//     * @access public
//     * @return int
//     * @param int $office_bank_id
//     */
//    public function getActiveChequeBook(int $office_bank_id): int
//    {
//
//        $cheque_book_id = 0;
//
//        //$this->read_db->select(['cheque_book_id']);
//        //$this->read_db->where(['cheque_book_is_active' => 1, 'fk_office_bank_id' => $office_bank_id]);
//        //$result_obj = $this->read_db->get('cheque_book');
//
//        $builder = $this->read_db->table('cheque_book');
//        $builder->select(['cheque_book_id']);
//        $builder->where(['cheque_book_is_active' => 1, 'fk_office_bank_id' => $office_bank_id]);
//        $result_obj = $builder->get();
//
//        if ($result_obj->getNumRows() > 0) {
//
//            $cheque_book_id = $result_obj->getRow()->cheque_book_id;
//        }
//
//        return $cheque_book_id;
//    }

    // public function save_cancelled_cheques(array $post_arr):int
    // {
    //     $insert_status = 1;

    //     $batch_of_data = [];

    //     $post = $post_arr;

    //     $this->write_db->trans_start();

    //     $cheque_numbers = $post['cancel_cheque_number'];

    //     foreach ($cheque_numbers as $cheque_number) {

    //         //log_message('error',$cheque_number);
    //         $data['fk_cheque_book_id'] = $post['fk_cheque_book_id'];

    //         $data['cancel_cheque_number'] = $cheque_number;

    //         $data['cancel_cheque_name'] = $this->grants_model->generate_item_track_number_and_name('cancel_cheque')['cancel_cheque_name'];

    //         $track = $this->grants_model->generate_item_track_number_and_name('cancel_cheque');

    //         $data['cancel_cheque_track_number'] = $track['cancel_cheque_track_number'];

    //         $data['cancel_cheque_created_date'] = date('Y-m-d');

    //         $data['cancel_cheque_created_by'] = $this->session->user_id;

    //         $data['fk_status_id'] = $this->grants_model->initial_item_status('cancel_cheque');

    //         $batch_of_data[] = $data;

    //     }
    //     //Insert Data
    //     $this->write_db->insert_batch('cancel_cheque', $batch_of_data);

    //     $this->write_db->trans_complete();

    //     if ($this->write_db->trans_status() == false) {
    //         $insert_status = 0;
    //     }

    //     return $insert_status;

    // }

   
}