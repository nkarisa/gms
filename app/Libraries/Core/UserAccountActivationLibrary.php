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
      'user_account_activation_track_number',
      'user_account_activation_reject_reason',
      'user_account_activation_name',
      'user_email',
      'role_name',
      'user_works_for',
      'user_account_activation_created_date',
      'user_activator_ids'
      
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
   
}