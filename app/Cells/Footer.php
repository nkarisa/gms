<?php 

namespace App\Cells;

class Footer
{
    public function show(): string
    {
        return view("components/footer");
    }
}