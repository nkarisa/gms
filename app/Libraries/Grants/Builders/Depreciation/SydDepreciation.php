<?php

namespace App\Libraries\Grants\Builders\Depreciation;

class SydDepreciation implements DepreciationMethod
{
    protected float $costOfAsset;
    protected float $salvageValue;
    protected int $usefulLife;

    public function __construct(float $costOfAsset, float $salvageValue, int $usefulLife)
    {
        $this->costOfAsset = $costOfAsset;
        $this->salvageValue = $salvageValue;
        $this->usefulLife = $usefulLife;
    }

    public function getDepreciationSchedule(): array
    {
        $schedule = [];
        $depreciableBase = $this->costOfAsset - $this->salvageValue;
        $totalMonths = $this->usefulLife * 12;

        // Calculate the sum of the years' digits
        $syd = $this->usefulLife * ($this->usefulLife + 1) / 2;

        $currentBookValue = $this->costOfAsset;
        $accumulatedDepreciation = 0;

        // Loop through each year
        for ($year = 1; $year <= $this->usefulLife; $year++) {
            // Get the remaining years for the numerator
            $remainingLife = $this->usefulLife - $year + 1;
            $annualDepreciation = ($remainingLife / $syd) * $depreciableBase;
            $monthlyDepreciation = $annualDepreciation / 12;

            // Loop through each month in the current year
            for ($month = 1; $month <= 12; $month++) {
                $beginningValue = $currentBookValue;
                $depreciationExpense = $monthlyDepreciation;

                // Adjust the final month's depreciation to reach salvage value
                if (($currentBookValue - $monthlyDepreciation) < $this->salvageValue) {
                    $depreciationExpense = $currentBookValue - $this->salvageValue;
                }

                $accumulatedDepreciation += $depreciationExpense;
                $endValue = $beginningValue - $depreciationExpense;
                $currentBookValue = $endValue;

                $schedule[] = [
                    'year' => $year,
                    'month' => $month,
                    'month_beginning_asset_value' => round($beginningValue, 2),
                    'month_depreciation_expense' => round($depreciationExpense, 2),
                    'accumulated_depreciation_expense' => round($accumulatedDepreciation, 2),
                    'month_end_asset_value' => round($endValue, 2),
                ];
            }
        }

        return $schedule;
    }
}