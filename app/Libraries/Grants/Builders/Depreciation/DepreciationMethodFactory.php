<?php

namespace App\Libraries\Grants\Builders\Depreciation;

class DepreciationMethodFactory
{
    protected float $assetValue;
    protected float $usefulLife;
    protected int $salvageValue;
    protected float $multiplier;
    protected $depreciationMethod;

    public function __construct(){}

    public function setAssetValue($assetValue){
        $this->assetValue = $assetValue;
        return $this;
    }

    public function setUsefulLife($usefulLife){
        $this->usefulLife = $usefulLife;
        return $this;
    }

    public function setSalvageValue($salvageValue){
        $this->salvageValue = $salvageValue;
        return $this;
    }

    public function setMultiplier($multiplier){
        $this->multiplier = $multiplier;
        return $this;
    }

    public function setDepreciationMethod($depreciationMethod){
        $this->depreciationMethod = $depreciationMethod;
        return $this;
    }

    public function createDepreciationMethod(): DepreciationMethod
    {
        return match ($this->depreciationMethod) {
            'straight' => new StraightLineDepreciation($this->assetValue, $this->salvageValue, $this->usefulLife),
            'declining' => new DecliningBalanceDepreciation($this->assetValue, $this->salvageValue, $this->usefulLife, $this->multiplier),
            'sum_of_years_digits' => new SydDepreciation($this->assetValue, $this->salvageValue, $this->usefulLife),
            default => new StraightLineDepreciation($this->assetValue, $this->salvageValue, $this->usefulLife)
        };
    }
}

