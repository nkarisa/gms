<?php 

namespace App\Cells;

class Content
{
    public function show(array $params): string
    {
        return view("components/".$params['action']);
    }
}