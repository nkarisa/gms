<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\UserAccountActivationModel;
class UserAccountActivationLibrary extends GrantsLibrary
{

    protected $table;
    protected $useraccountactivationModel;

    function __construct()
    {
        parent::__construct();

        $this->useraccountactivationModel = new UserAccountActivationModel();

        $this->table = 'user_account_activation';
  }

  function additionalListColumns(): array {
    $additionalListColumns = ['activate_or_reject_user'];
    return ['positionAfter' => 'user_account_activation_id', 'columns' => $additionalListColumns];
  }

  function formatColumnsValues(string $column, mixed $columnValue, array $rowArray): mixed
  {
      if ($column == 'activate_or_reject_user') {
          $columnValue = '<div style="white-space:nowrap;"><input class="form-check-input" type="checkbox" value="" id="chechbox_' . $rowArray['user_account_activation_id'] . '"> <button class="btn btn-success"  id="activate_' . $rowArray['user_account_activation_id'] . '">Activate?</button> <button class="btn btn-danger"  id="reject_' . $rowArray['user_account_activation_id'] . '">Reject?</button> </div>';
      }
      
      if($column == "user_account_activation_reject_reason"){
        $data['user_account_activation_id'] = $rowArray['user_account_activation_id'];
        $columnValue = view("user_account_activation/reject_reason", $data);
      }

      return $columnValue;
  }

  function pagePosition(){
    $widget['position_1']['list'][] = view("user_account_activation/buttons");
    return $widget;
  }

  /**
   * columns(): returns columns to be used select clause in DB.
   * @author Onduso 
   * @access public 
   * @return array
   * @Dated: 17/8/2023
   */
  public function listTableVisibleColumns():array
  {
    $columns = [
      'user_account_activation_id',
      'user_account_activation_reject_reason',
      'user_account_activation_name',
      'user_email',
      'user_works_for',
      'user_account_activation_created_date',
    //   'user_activator_ids'
    ];

    return $columns;
  }

    /**
   * get_users_for_activation(): returns an array of users to be activated by either admin/pf/super admins
   * @author Onduso 
   * @access public 
   * @return array
   * @Dated: 18/8/2023
   */
  public function list($builder, array $selectColumns, $parentId = null, $parentTable = null):array
  {
    // log_message('error', json_encode($selectColumns));
    $selectColumns = array_values($selectColumns);
    $this->dataTableBuilder($builder, $this->controller, $selectColumns);
    //Get records
    $builder->select($selectColumns);
    $builder->where(['deleted_at'=> NULL]);
    $builder->join('user','user.user_id=user_account_activation.fk_user_id');
    $builder->join('role','role.role_id=user.fk_role_id');
    $result_obj = $builder->get();

    $results = [];

    if ($result_obj->getNumRows() > 0) {
      $results = $result_obj->getResultArray();
    }

    return compact('results');
  }

      /**
     * get_user_account_id(): returns user account id that need to be activated to each logged in user.
     * @author Onduso 
     * @access public 
     * @return array
     * @Dated: 18/8/2023
     * @param int $user_activation_id.
     */
    public function activateNewUserAccount(int $user_activation_id): int
    {   
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        //Get user to activate from user_activation tabel and activate user in user table.
        $builder = $this->read_db->table("user_account_activation");
        $builder->select(['fk_user_id']);
        $builder->where(['user_account_activation_id' => $user_activation_id]);
        $fk_user_id = $builder->get()->getRow()->fk_user_id;

        $builder = $this->read_db->table("user");
        $builder->where(['user_id'=>$fk_user_id]);
        $account_system_id = $builder->get()->getRow()->fk_account_system_id;

        $this->write_db->transStart();
        //Update the user Table to activate newly created user
        $update_user['user_is_active']=1;
        $update_user['user_first_time_login']=1;
        $update_user['user_self_created']=1;
        $update_user['user_created_by']=$this->session->user_id;

        $max_status_id = $statusLibrary->getMaxApprovalStatusId('user', [], $account_system_id);
        $update_user['fk_status_id']= $max_status_id[0];

        $builder = $this->write_db->table("user");
        $builder->where(['user_id'=>$fk_user_id]);
        $builder->update($update_user);

        //Delete the user once activated
        $builder = $this->write_db->table("user_account_activation");
        $builder->where(['user_account_activation_id'=>$user_activation_id]);
        $builder->delete();

        $this->write_db->transComplete();

        if ($this->write_db->affectedRows() == '1') {
            return 1;
        } else {
            // any trans error?
            if ($this->write_db->transStatus() === FALSE) {
                return 0;
            }
            return 1;
        }
    }


     /**
     * reject_activating_new_user_account(): deletes the user from user, context user related table and department_user table.
     * @author Onduso 
     * @access public 
     * @return array
     * @Dated: 18/8/2023
     * @param int $user_activation_id.
     */
    public function rejectActivatingNewUserAccount(int $user_activation_id, string $userRejectionReson):int
    {
        
        //Get user to activate from user_activation table and activate user in user table.
        $builder = $this->read_db->table('user_account_activation');
        $builder->select(['fk_user_id','user_type']);
        $builder->where(['user_account_activation_id' => $user_activation_id]);
        $new_account_details = $builder->get()->getResultArray();

        $user_ids = array_column($new_account_details,'fk_user_id');
        $user_type = array_column($new_account_details,'user_type');

        $this->write_db->transStart();

        switch($user_type[0]){
            case 1:
               //Delete user in context_center_user
               $builder = $this->write_db->table('context_center_user');
               $builder->where(['fk_user_id'=>$user_ids[0]]);
               $builder->delete();
               break;
            case 2: 
               //Delelet user in context_cluster_user
               $builder = $this->write_db->table('context_cluster_user');
               $builder->where(['fk_user_id'=>$user_ids[0]]);
               $builder->delete();
               break;
            case 3: 
               //Delete user from context_cohort_user
               $builder = $this->write_db->table('context_cohort_user');
               $builder->where(['fk_user_id'=>$user_ids[0]]);
               $builder->delete();
               break;
            case 4: 
               //Delete user from context_country_user [country admins]
               $builder = $this->write_db->table('context_country_user');
               $builder->where(['fk_user_id'=>$user_ids[0]]);
               $builder->delete();
               break;

            case 5:
                //Delete user from context_country_user [Other national staffs]
                $builder = $this->write_db->table('context_country_user');
                $builder->where(['fk_user_id'=>$user_ids[0]]);
                $builder->delete();
                break;
        }

        //Delete user from department_user table
        $builder = $this->write_db->table('department_user');
        $builder->where(['fk_user_id'=>$user_ids[0]]);
        $builder->delete();

        //Delete user from user table
        $builder = $this->write_db->table('user');
        $builder->where(['user_id'=>$user_ids[0]]);
        $builder->delete();

        //Soft delete on user_account_activation
        $put_delete_at_marker['deleted_at']=date('Y-m-d');
        $put_delete_at_marker['fk_user_id']=0;
        $put_delete_at_marker['user_account_activation_reject_reason']=$userRejectionReson;

        $builder = $this->write_db->table('user_account_activation');
        $builder->where(['user_account_activation_id'=>$user_activation_id]);
        $builder->update($put_delete_at_marker);

        $this->write_db->transComplete();

        if ($this->write_db->affectedRows() == '1') {
            return 1;
        } else {
            // any transaction error?
            if ($this->write_db->transStatus() === FALSE) {
                return 0;
            }
            return 1;
        }
    }
   
}