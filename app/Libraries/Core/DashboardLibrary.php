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
            'message' => 'success',
            'dashboards' => [
                // 'total_users' => $this->dashboardModel->countAll(),
                // 'total_grants' => $this->dashboardModel->getTotalGrants(),
                // 'total_funds' => $this->dashboardModel->getTotalFunds(),
                // 'total_projects' => $this->dashboardModel->getTotalProjects(),
                // 'total_applications' => $this->dashboardModel->getTotalApplications(),
                // 'total_funds_transferred' => $this->dashboardModel->getTotalFundsTransferred(),
                // 'total_financial_reports' => $this->dashboardModel->getTotalFinancialReports(),
                // 'total_vouchers' => $this->dashboardModel->getTotalVouchers(),
                // 'total_voucher_details' => $this->dashboardModel->getTotalVoucherDetails(),
                // 'total_grants_pending' => $this->dashboardModel->getTotalGrantsPending(),
            ]
        ];

        return $result;
    }
   
}