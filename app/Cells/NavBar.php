<?php 

namespace App\Cells;

use App\Libraries\Core\MenuLibrary;

class NavBar
{
    public function show(): string
    {
        $menuLibrary = new MenuLibrary();
        $navItems = $menuLibrary->navigationItems();
        return view("components/navigation", ['navItems' => $navItems]);
    }
}