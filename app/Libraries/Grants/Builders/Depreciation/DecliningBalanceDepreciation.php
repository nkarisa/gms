<?php

namespace App\Libraries\Grants\Builders\Depreciation;

class DecliningBalanceDepreciation implements DepreciationMethod
{
    protected float $costOfAsset;
    protected float $salvageValue;
    protected int $usefulLife;
    protected float $multiplier;

    public function __construct(float $costOfAsset, float $salvageValue, int $usefulLife, float $multiplier = 2.0)
    {
        $this->costOfAsset = $costOfAsset;
        $this->salvageValue = $salvageValue;
        $this->usefulLife = $usefulLife;
        $this->multiplier = $multiplier;
    }

    public function getDepreciationSchedule(): array
    {
        $schedule = [];
        $currentBookValue = $this->costOfAsset;
        $accumulatedDepreciation = 0;
        
        $totalMonths = $this->usefulLife * 12;
        $monthlyDepreciationRate = ($this->multiplier / $this->usefulLife) / 12;

        for ($month = 1; $month <= $totalMonths; $month++) {
            $beginningValue = $currentBookValue;
            $depreciationExpense = 0;

            if ($beginningValue > $this->salvageValue) {
                // Calculate the monthly depreciation
                $depreciationExpense = $beginningValue * $monthlyDepreciationRate;

                // Ensure the book value doesn't drop below the salvage value
                if (($beginningValue - $depreciationExpense) < $this->salvageValue) {
                    $depreciationExpense = $beginningValue - $this->salvageValue;
                }
            }

            $accumulatedDepreciation += $depreciationExpense;
            $endValue = $beginningValue - $depreciationExpense;
            $currentBookValue = $endValue;

            if(round($depreciationExpense, 2) == 0) break;

            $schedule[] = [
                'month' => $month,
                'month_beginning_asset_value' => round($beginningValue, 2),
                'month_depreciation_expense' => round($depreciationExpense, 2),
                'accumulated_depreciation_expense' => round($accumulatedDepreciation, 2),
                'month_end_asset_value' => round($endValue, 2),
            ];
        }

        return $schedule;
    }
}