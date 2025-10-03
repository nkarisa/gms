<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Enums\EarningTypes;
use App\Libraries\Grants\{EarningLibrary, PayrollDeductionLibrary, PayslipLibrary};


class Payslip extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        
    }

    public function getOfficePayslipDetails()
    {
        $post = $this->request->getJSON();
        $payslipId = $post->payslipId;

        $payslipLibrary = new PayslipLibrary();
        $officeData = $payslipLibrary->getOfficePayslipDetails($payslipId);

        return $this->response->setJSON($officeData);
    }

    public function getPayslipData()
    {
        $post = $this->request->getJSON();
        $payslipId = $post->payslipId;

        $payslipLibrary = new PayslipLibrary();
        $payslip = $payslipLibrary->getPayslipDetails($payslipId);

        return $this->response->setJSON($payslip);
    }

    public function getPayslipSectionOptions($payslipId)
    {
        $payslipLibrary = new PayslipLibrary();
        $payslipOptions = $payslipLibrary->payslipOptions($payslipId);
        return $this->response->setJSON($payslipOptions);
    }

    public function updatePayslip()
    {
        $earningLibrary = new EarningLibrary();
        $payrollDeductionLibrary = new PayrollDeductionLibrary();
        $payslipLibrary = new PayslipLibrary();

        $post = $this->request->getJSON();
        $payslipId = $post->payslipId;

        // Get Payslip Pay History
        $payHistory = $payslipLibrary->getPayslipPayHistory($payslipId);

        if (!empty($payHistory)) {
            $payHistoryId = $payHistory['pay_history_id'];

            $earningLibrary->earningUpsert($post->earnings, $payHistoryId, EarningTypes::PAYABLE);
            $payrollDeductionLibrary->deductionUpsert($post->deductions, $payHistoryId, $payslipId);
            $earningLibrary->earningUpsert($post->benefits, $payHistoryId, EarningTypes::ACCRUED);
            
        }

        return $this->response->setJSON($post);
    }

}
