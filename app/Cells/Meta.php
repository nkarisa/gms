<?php 

namespace App\Cells;

class Meta
{
    public function show(): string
    {
        $text_align = 'left-to-right';
        return $text_align == 'right-to-left' ? 'rtl' : 'ltr';
    }
}