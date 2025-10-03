<?php 

namespace App\Libraries\System\Types;

class EarningsConfig {
    public bool $show_accrued_earnings = true;
    public bool $show_taxable_earnings = true;
    public bool $show_basic_earnings = true;
    public bool $show_recurring_earnings = true;
    public bool $show_payable_earnings = true;

    public function __construct($configurations) {
        $this->show_accrued_earnings = $configurations['show_accrued_earnings'] ?? true;
        $this->show_taxable_earnings = $configurations['show_taxable_earnings'] ?? true;
        $this->show_basic_earnings = $configurations['show_basic_earnings'] ?? true;
        $this->show_recurring_earnings = $configurations['show_recurring_earnings'] ?? true;
        $this->show_payable_earnings = $configurations['show_payable_earnings'] ?? true;
    }
}