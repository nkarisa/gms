<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AccrualClearance extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    public function getActionOfficeBanks($officeId)
    {

        $officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();
        $officeBanks = $officeBankLibrary->getActiveOfficeBank($officeId);

        return $this->response->setJSON($officeBanks);
    }

    public function getAccrualTransactionDetails($voucherId)
    {
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $voucherTransaction = $voucherLibrary->voucherTransactionWithSumDetailPerAccount($voucherId, 'receivables');

        // log_message('info', json_encode($voucherTransaction));


        $response = [
            'account' => 'R100 - Support Funds',
            'original_amount' => 210000,
            'uncleared_amount' => 210000,
        ];

        return $this->response->setJSON($response);
    }

    public function getUnclearedAccrualTransactions()
    {

        $officeIds = [];

        if(!$this->session->system_admin){
            $officeIds = array_column($this->session->hierarchy_offices,'office_id');
        }

        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $unclearedAccrualTransactions = $voucherLibrary->UnclearedAccrualTransactions($officeIds);

        // log_message('info', json_encode($unclearedAccrualTransactions));
        $accrualList = [];
        foreach($unclearedAccrualTransactions as $voucher){
            $accrualList[] = [
                'voucher_hashed_id' => hash_id($voucher['voucher_id'], 'encode'),
                'voucher_number' => $voucher['voucher_number'],
                'voucher_id' => $voucher['voucher_id'],
                'office_id' => $voucher['office_id'],
                'description' => $voucher['voucher_description'],
                'amount' => $voucher['voucher_detail_total_cost'],
            ];
        }

        return $this->response->setJSON($accrualList);
    }

    public function getUnclearedTransactionDetails($voucherId){

        $incomeAccountsWithUnclearedAmounts = [
            ['income_account_id' => 1, 'income_account_name' => 'R100-Child Support', 'income_account_code' => 'R100', 'uncleared_amount' => 250000],
            ['income_account_id' => 2, 'income_account_name' => 'R200-Gifts', 'income_account_code' => 'R200', 'uncleared_amount' => 150000]
        ];

        return $this->response->setJSON($incomeAccountsWithUnclearedAmounts);
    }
}
