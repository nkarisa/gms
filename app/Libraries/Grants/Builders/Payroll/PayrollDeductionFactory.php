<?php 

namespace App\Libraries\Grants\Builders\Payroll;

class PayrollDeductionFactory {
    // Method to create a new deduction instance based on the provided product name
    public function createDeductionProduct(string $deducationProduct): ?DeductionInterface {
        // Use class_exists() to verify the class before instantiation
        
        $className = "\\App\\Libraries\\Grants\\Builders\\Payroll\\Products\\{$deducationProduct}";

        if (class_exists($className)) {
            // Create a new instance if the class exists
            $productInstance = new $className();

            // Check if the instance implements the required interface
            if ($productInstance instanceof DeductionInterface) {
                return $productInstance;
            } else {
                // Handle the case where the class doesn't implement the interface
                error_log("Class '{$deducationProduct}' does not implement DeductionInterface.");
                return null;
            }
        } else {
            // Handle the case where the class does not exist
            error_log("Class '{$deducationProduct}' not found.");
            return null;
        }
    }
}