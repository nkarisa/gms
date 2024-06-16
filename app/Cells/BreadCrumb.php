<?php 

namespace App\Cells;

class BreadCrumb
{
    public function show(): string
    {
        $create_breadcrumb = '';
        return view('components/breadcrumb', ['create_breadcrumb' => $create_breadcrumb]);;
    }
}