<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\UserSwitchModel;
class UserSwitchLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new UserSwitchModel();

        $this->table = 'user';

    }

    private function activeCenterUsersWithOfficeCodes(): array {

        $users = [];

        $builder = $this->read_db->table($this->table);

        $builder->select(array('user_id','office_code'));
        $builder->join('context_center_user','context_center_user.fk_user_id=user.user_id');
        $builder->join('context_center','context_center.context_center_id=context_center_user.fk_context_center_id');
        $builder->join('office','office.office_id=context_center.fk_office_id');

        if(!$this->session->get('system_admin')){
            $builder->where(array('user.fk_account_system_id'=>$this->session->get('user_account_system_id')));
        }

        $builder->where(array('user_is_active' => 1));
        $users_obj = $builder->get();

        if($users_obj->getNumRows() > 0){
            $users_raw = $users_obj->getResultArray();

            foreach($users_raw as $user){
                $users[$user['user_id']][] = $user['office_code'];
            }
        }

        return $users;
    }


    public function getSwitchableUsers(): array
    {
        $activeCenterUsers = $this->activeCenterUsersWithOfficeCodes();
        $session = session();

        $builder = $this->read_db->table($this->table)
            ->select([
                'user.user_id',
                "CONCAT(user.user_firstname, ' ', user.user_lastname, ' [', user.user_email, ' - ', role.role_name, ']') as user_name",
                'role.role_id',
                'role.role_name'
            ])
            ->join('role', 'role.role_id = user.fk_role_id');

        if (!$session->get('system_admin')) {
            $builder->where('user.fk_account_system_id', $session->get('user_account_system_id'));
        }

        $contextDefinition = $session->get('context_definition');
        $contextDefinitionName = $contextDefinition['context_definition_name'];
        $contextDefinitionId = $contextDefinition['context_definition_id'];
        $userOffices = array_column($session->get('hierarchy_offices'), 'office_id');

        // Restrict users to hierarchy offices based on context
        if ($contextDefinitionName === 'center' || $contextDefinitionName === 'cluster') {
            $builder->join('context_center_user', 'context_center_user.fk_user_id = user.user_id')
                    ->join('context_center', 'context_center.context_center_id = context_center_user.fk_context_center_id')
                    ->join('office', 'office.office_id = context_center.fk_office_id')
                    ->whereIn('context_center.fk_office_id', $userOffices);
        }

        // Restrict users to context levels equal to or below the current user
        $builder->where('user.fk_context_definition_id <=', $contextDefinitionId);

        // Prevent switching to self and filter active and switchable users
        $builder->whereNotIn('user.user_id', [$session->get('user_id')])
                ->where([
                    'user.user_is_active' => 1,
                    'user.user_is_switchable' => 1
                ]);

        $usersList = $builder->get()->getResultArray();
        
        // Convert users into a keyed array
        $userIds = array_column($usersList, 'user_id');
        $userNames = array_column($usersList, 'user_name');
        $users = array_combine($userIds, $userNames);

        // Append active center users details if available
        foreach ($users as $userId => $userDetail) {
            if (isset($activeCenterUsers[$userId])) {
                $users[$userId] .= ' [' . implode(',', $activeCenterUsers[$userId]) . ']';
            }
        }

        return $users;
    }
   
}