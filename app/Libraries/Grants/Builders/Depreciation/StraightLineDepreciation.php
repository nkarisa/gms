<?php

namespace App\Libraries\Grants\Builders\Depreciation;

class StraightLineDepreciation implements DepreciationMethod
{
    protected float $costOfAsset;
    protected float $salvageValue;
    protected int $usefulLife;
    protected float $depreciableBase;
    protected float $monthlyDepreciation;

    public function __construct(float $costOfAsset, float $salvageValue, int $usefulLife)
    {
        $this->costOfAsset = $costOfAsset;
        $this->salvageValue = $salvageValue;
        $this->usefulLife = $usefulLife;

        // Calculate the depreciable base and monthly depreciation
        $this->depreciableBase = $this->costOfAsset - $this->salvageValue;
        $this->monthlyDepreciation = $this->depreciableBase / ($this->usefulLife * 12);
    }

    public function getDepreciationSchedule(): array
    {
        $schedule = [];
        $currentBookValue = $this->costOfAsset;
        $accumulatedDepreciation = 0;
        $totalMonths = $this->usefulLife * 12;

        for ($month = 1; $month <= $totalMonths; $month++) {
            $beginningValue = $currentBookValue;
            $depreciationExpense = $this->monthlyDepreciation;

            // Ensure the final month doesn't depreciate below salvage value
            if (($currentBookValue - $depreciationExpense) < $this->salvageValue) {
                $depreciationExpense = $currentBookValue - $this->salvageValue;
            }
            
            $accumulatedDepreciation += $depreciationExpense;
            $endValue = $beginningValue - $depreciationExpense;
            $currentBookValue = $endValue;

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