<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BudgetLimit extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function getSetBudgetLimit()
    {
        $post = $this->request->getPost();

        $previous_budget_limits = $this->getPreviousBudgetLimits($post);

        echo json_encode($previous_budget_limits);
    }


    public function getPreviousBudgetLimits($newPost)
    {

        $budgetLib = new \App\Libraries\Grants\BudgetLibrary();

        $officeId = $newPost['office_id'] ?? 0;
        $budgetYear = $newPost['budget_year'] ?? 0;

        // Get the custom financial year
        $customFinancialYear = $budgetLib->getCustomFinancialYear($officeId, true);
        $customFinancialYearId = $customFinancialYear['id'] ?? 0;

        // Build query for budget limits
        $builder = $this->read_db->table('budget')
            ->select(['budget_id', 'fk_office_id', 'fk_budget_tag_id'])
            ->where('budget_year', $budgetYear)
            ->where('fk_office_id', $officeId);

        if ($customFinancialYearId > 0) {
            $builder->where('fk_custom_financial_year_id', $customFinancialYearId);
        }

        $builder->orderBy('fk_budget_tag_id', 'DESC');
        $yearsOfficeBudget = $builder->get()->getResultArray();

        $budgetLimits = [];

        if (!empty($yearsOfficeBudget)) {
            $latestYearReview = $yearsOfficeBudget[0];

            // Get budget limits
            $budgetLimitBuilder = $this->read_db->table('budget_limit')
                ->select(['income_account_id', 'income_account_name', 'budget_limit_amount'])
                ->join('income_account', 'income_account.income_account_id = budget_limit.fk_income_account_id')
                ->where('fk_budget_id', $latestYearReview['budget_id'])
                ->where('income_account_is_active', 1)
                ->where('income_account_is_budgeted', 1);

            $budgetLimits = $budgetLimitBuilder->get()->getResultArray();
        }

        return $budgetLimits;
    }
}
