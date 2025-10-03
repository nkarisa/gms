<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\PayHistoryModel;
use DateTime;
class PayHistoryLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $payhistoryModel;

    function __construct()
    {
        parent::__construct();

        $this->payhistoryModel = new PayHistoryModel();

        $this->table = 'pay_history';
    }

    function detailTables(): array
    {
        return ['earning'];
    }

    function listTableVisibleColumns(): array
    {
        return [
            'pay_history_track_number',
            'office_name',
            'user_name',
            'pay_history_start_date',
            'pay_history_end_date',
            'pay_history_total_earning_amount',
            'pay_history_created_date'
        ];
    }

    function singleFormAddVisibleColumns(): array
    {
        return [
            'office_name',
            'user_name',
            'pay_history_start_date',
            'pay_history_end_date',
        ];
    }

    function editVisibleColumns(): array
    {
        return [
            'office_name',
            'user_name',
            'pay_history_start_date',
            'pay_history_end_date',
        ];
    }

    function lookupValues(): array
    {
        $lookUpValues = parent::lookupValues();

        if (!$this->session->system_admin) {
            $officeBuilder = $this->read_db->table('office');

            $officeBuilder->select(['office_id', 'office_name']);
            $officeBuilder->whereIn('office_id', array_column($this->session->hierarchy_offices, 'office_id'));
            $officeObj = $officeBuilder->get();

            $lookUpValues['office'] = [];

            if ($officeObj->getNumRows() > 0) {
                $lookUpValues['office'] = $officeObj->getResultArray();
            }
        }

        $userBuilder = $this->read_db->table('user');
        $userBuilder->select(['user_id', 'CONCAT(user_firstname, " ", user_lastname) as user_name']);
        $userBuilder->where('user_is_active', 1);
        $userBuilder->join('context_center_user', 'context_center_user.fk_user_id=user.user_id');
        $userBuilder->join('context_center', 'context_center.context_center_id=context_center_user.fk_context_center_id');

        if (!$this->session->system_admin) {
            $userBuilder->whereIn('context_center.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        }

        $userObj = $userBuilder->get();
        $lookUpValues['user'] = [];

        if ($userObj->getNumRows() > 0) {
            $lookUpValues['user'] = $userObj->getResultArray();
        }

        return $lookUpValues;
    }

    function formatColumnsValues(string $columnName, mixed $columnValue, array $rowArray, array $dependancyData = []): mixed
    {
        if ($columnName == 'user_name') {
            $columnValue = $rowArray['user_firstname'] . ' ' . $rowArray['user_lastname'];
        }

        return $columnValue;
    }

    public function getUserLatestPayHistory($userId)
    {
        // Get Latest User Pay History Revision
        $payHistoryBuilder = $this->read_db->table('pay_history');

        $payHistoryBuilder->select(['pay_history_id', 'CONCAT(user_firstname," ",user_lastname, "[",pay_history_start_date, " - ", pay_history_end_date,"]") as pay_history_name']);
        $payHistoryBuilder->join('user', 'user.user_id=pay_history.fk_user_id');
        $payHistoryBuilder->where('fk_user_id', $userId);
        $payHistoryObj = $payHistoryBuilder->get();

        if ($payHistoryObj->getNumRows() > 0) {
            return $payHistoryObj->getLastRow();
        }

        return null;
    }

    // function getLastPayHistory($userId){
    //     $payHistoryBuilder = $this->read_db->table('pay_history');

    //     $payHistoryBuilder->select('pay_history_id, pay_history_start_date, pay_history_end_date');
    //     $payHistoryBuilder->where('fk_user_id', $userId);
    //     $payHistoryBuilder->orderBy('pay_history_id', 'DESC'); // Order by the highest ID first
    //     $payHistoryBuilder->limit(1);
    //     $payHistoryObj = $payHistoryBuilder->get();

    //     $lastPayHistory = [];

    //     if($payHistoryObj->getNumRows() > 0){
    //         $lastPayHistory = $payHistoryObj->getRowArray();
    //     }

    //     return $lastPayHistory;
    // }

    // function isNewPeriodOverlapping($postData, $lastPayHistory){
    //     // Post Range
    //     $post_start_date = new DateTime($postData['pay_history_start_date']);
    //     $post_end_date = new DateTime($postData['pay_history_end_date']);

    //     if(empty($lastPayHistory)){
    //         return false;
    //     }

    //     // Last Pay History Range
    //     $pay_history_start_date = new DateTime($lastPayHistory['pay_history_start_date']);
    //     $pay_history_end_date = new DateTime($lastPayHistory['pay_history_end_date']);

    //     return checkDateRangesOverlap($post_start_date, $post_end_date, $pay_history_start_date, $pay_history_end_date);
    // }

    function savePayHistory($postData)
    {

        $response = ['flag' => false, 'message' => 'Failed to generate pay history', 'header_id' => null];
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $this->write_db->transStart();

        // // Get last pay history for the user
        // $lastPayHistory = $this->getLastPayHistory($postData['fk_user_id']);

        // // Check if dates overlap
        // $isOverlapPeriod = $this->isNewPeriodOverlapping($postData, $lastPayHistory);

        // if($isOverlapPeriod){
        //     $response['message'] = "Failed to pay history due overlap date ranges with an already existing pay history";
        //     return $response;
        // }

        $nameAndTrackNumber = $this->generateItemTrackNumberAndName('pay_history');

        $payHistoryInsertData['pay_history_name'] = $nameAndTrackNumber['pay_history_name'];
        $payHistoryInsertData['pay_history_track_number'] = $nameAndTrackNumber['pay_history_track_number'];
        $payHistoryInsertData['fk_office_id'] = $postData['fk_office_id'];
        $payHistoryInsertData['fk_user_id'] = $postData['fk_user_id'];
        $payHistoryInsertData['pay_history_start_date'] = $postData['pay_history_start_date'];
        $payHistoryInsertData['pay_history_end_date'] = $postData['pay_history_end_date'];
        $payHistoryInsertData['pay_history_total_earning_amount'] = array_sum($postData['earning_amount']);
        $payHistoryInsertData['pay_history_created_date'] = date('Y-m-d');
        $payHistoryInsertData['pay_history_created_by'] = $this->session->user_id;
        $payHistoryInsertData['pay_history_last_modified_by'] = $this->session->user_id;
        $payHistoryInsertData['fk_status_id'] = $statusLibrary->initialItemStatus('pay_history');


        // $response = $this->add(new \App\Libraries\System\Types\PostData($payHistoryInsertData, 'pay_history', false));
        // $payHistoryId = $response['headerId'];

        $this->write_db->table('pay_history')->insert($payHistoryInsertData);

        $payHistoryId = $this->write_db->insertID();

        $cnt = 0;
        foreach ($postData['fk_earning_category_id'] as $earning_category_id) {
            $nameAndTrackNumber = $this->generateItemTrackNumberAndName('earning');

            $earningInsertdata[$cnt]['earning_name'] = $nameAndTrackNumber['earning_name'];
            $earningInsertdata[$cnt]['earning_track_number'] = $nameAndTrackNumber['earning_track_number'];
            $earningInsertdata[$cnt]['fk_pay_history_id'] = $payHistoryId;
            $earningInsertdata[$cnt]['fk_earning_category_id'] = $earning_category_id;
            $earningInsertdata[$cnt]['earning_amount'] = $postData['earning_amount'][$cnt];
            $earningInsertdata[$cnt]['earning_created_date'] = '';
            $earningInsertdata[$cnt]['earning_created_by'] = '';
            $earningInsertdata[$cnt]['earning_last_modified_by'] = '';

            $cnt++;

            // $this->add(new \App\Libraries\System\Types\PostData($earningInsertdata,'earning', false));
        }

        if (!empty($earningInsertdata)) {
            $this->write_db->table('earning')->insertBatch($earningInsertdata);
        }


        // End Transaction
        $this->write_db->transComplete();

        if ($this->write_db->transStatus() != FALSE) {
            $response['flag'] = true;
            $response['message'] = 'Pay History created successfully';
            $response['header_id'] = $payHistoryId;
        }

        return $response;
    }


    private function getPayHistoryRecord($payHistoryId){
        $payHistoryBuilder = $this->read_db->table('pay_history');

        $payHistoryBuilder->select([
            'pay_history.*',
            'CONCAT(user_firstname," ", user_lastname) as user_fullname',
            'CONCAT(pay_history_start_date, " to ", pay_history_end_date) as period',
            'office_name',
            'pay_history_start_date',
            'pay_history_end_date',
            'pay_history.fk_office_id as office_id',
            'pay_history.fk_status_id as status_id'
        ]);

        $payHistoryBuilder->join('user', 'user.user_id=pay_history.fk_user_id');
        $payHistoryBuilder->join('context_center_user', 'context_center_user.fk_user_id=user.user_id');
        $payHistoryBuilder->join('context_center', 'context_center.context_center_id=context_center_user.fk_context_center_id');
        $payHistoryBuilder->join('office', 'office.office_id=context_center.fk_office_id');
        $payHistoryBuilder->where('pay_history_id', $payHistoryId);
        $payHistoryObj = $payHistoryBuilder->get();

        $payHistory = [];

        if ($payHistoryObj->getNumRows() > 0) {
            $payHistory = $payHistoryObj->getRowArray();
        }

        // Use a numeric value for easy calculation in edit mode later
        $payHistory['pay_history_total_earning_amount'] = (int)$payHistory['pay_history_total_earning_amount'];

        return $payHistory;
    }

    private function getPayHistoryEarnings($payHistoryId)
    {
        $earningbuilder = $this->read_db->table('earning');

        $earningbuilder->select('earning_category_name as name, earning_amount as amount');
        $earningbuilder->where('fk_pay_history_id', $payHistoryId);
        $earningbuilder->join('earning_category', 'earning_category.earning_category_id=earning.fk_earning_category_id');
        $earningObj = $earningbuilder->get();

        $earnings = [];

        if ($earningObj->getNumRows() > 0) {
            $earnings = $earningObj->getResultArray();
        }

        // Use a numeric value for easy calculation in edit mode later
        $earnings = array_map(function ($earning) {
            $earning['amount'] = (int) $earning['amount'];
            return $earning;
        }, $earnings);

        return $earnings;
    }

    function getStatusButton($accountSystemId, $statusId){
        $actionButonData = $this->actionButtonData('pay_history', $accountSystemId);
        return $actionButonData['item_status'][$statusId];
    }

    public function getPayHistory($payHistoryId)
    {
        $dataArray = [];
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $payHistory = $this->getPayHistoryRecord($payHistoryId);
        $accountSystemId = $officeLibrary->getOfficeAccountSystem($payHistory['office_id'])['account_system_id'];
        $statusLabel = $this->getStatusButton($accountSystemId, $payHistory['status_id'])['status_name'];

        if (!empty($payHistory)) {
            $earnings = $this->getPayHistoryEarnings($payHistoryId);

            $dataArray = [
                'data' => [
                    'pay_history_name' => $payHistory['pay_history_name'],
                    'pay_history_track_number' => $payHistory['pay_history_track_number'],
                    'user_fullname' => $payHistory['user_fullname'],
                    'pay_history_total_earning_amount' => $payHistory['pay_history_total_earning_amount'],
                    'period' => $payHistory['period'],
                    'office_name' => $payHistory['office_name'],
                    'pay_history_start_date' => $payHistory['pay_history_start_date'],
                    'pay_history_end_date' => $payHistory['pay_history_end_date'],
                    'earnings' => $earnings,
                    'status' => $statusLabel,
                ],
                'success' => true,
                'message' => "Data fetched successfully",
            ];

        }

        return $dataArray;
    }

}