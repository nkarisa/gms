<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\DashboardModel;
class DashboardLibrary extends GrantsLibrary
{

    protected $table;
    protected $dashboardModel;

    function __construct()
    {
        parent::__construct();

        $this->dashboardModel = new DashboardModel();

        $this->table = 'dashboard';
    }


    public function getDashboardData($data){
        $result = [
            'status' => 'success',
            'message' => 'Dashboard data fetched successfully',
            'data' => $data,
            'dashboards' => [
                // 'total_users' => $dashboardLibrary->countAll(),
                // 'total_grants' => $dashboardLibrary->getTotalGrants(),
                // 'total_funds' => $dashboardLibrary->getTotalFunds(),
                // 'total_projects' => $dashboardLibrary->getTotalProjects(),
                // 'total_applications' => $dashboardLibrary->getTotalApplications(),
                // 'total_funds_transferred' => $dashboardLibrary->getTotalFundsTransferred(),
                // 'total_financial_reports' => $dashboardLibrary->getTotalFinancialReports(),
                // 'total_vouchers' => $dashboardLibrary->getTotalVouchers(),
                // 'total_voucher_details' => $dashboardLibrary->getTotalVoucherDetails(),
                // 'total_grants_pending' => $dashboardLibrary->getTotalGrantsPending(),
            ]
        ];

        return $result;
    }
   
}