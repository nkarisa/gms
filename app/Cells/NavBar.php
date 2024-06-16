<?php 

namespace App\Cells;

class NavBar
{
    public function show(): string
    {
        $navItems = `
        <li class="menu_tab approval_flow">
                <a href="http://developer.guru:8001/grants/approval_flow/list">
                      <i class=""></i>
                      <span>Approval Flow</span>
                  </a>
              </li>
              
              <li class="menu_tab bank">
                <a href="http://developer.guru:8001/grants/bank/list">
                      <i class=""></i>
                      <span>Bank</span>
                  </a>
              </li>
              
              <li class="menu_tab budget">
                <a href="http://developer.guru:8001/grants/budget/list">
                      <i class=""></i>
                      <span>Budget</span>
                  </a>
              </li>
              
              <li class="menu_tab funder">
                <a href="http://developer.guru:8001/grants/funder/list">
                      <i class=""></i>
                      <span>Funder</span>
                  </a>
              </li>
              
              <li class="menu_tab income_account">
                <a href="http://developer.guru:8001/grants/income_account/list">
                      <i class=""></i>
                      <span>Income Account</span>
                  </a>
              </li>
              
              <li class="menu_tab journal">
                <a href="http://developer.guru:8001/grants/journal/list">
                      <i class=""></i>
                      <span>Journal</span>
                  </a>
              </li>
              
              <li class="menu_tab office">
                <a href="http://developer.guru:8001/grants/office/list">
                      <i class=""></i>
                      <span>Office</span>
                  </a>
              </li>
              
              <li class="menu_tab office_bank">
                <a href="http://developer.guru:8001/grants/office_bank/list">
                      <i class=""></i>
                      <span>Office Bank</span>
                  </a>
              </li>
              
              <li class="menu_tab office_cash">
                <a href="http://developer.guru:8001/grants/office_cash/list">
                      <i class=""></i>
                      <span>Office Cash</span>
                  </a>
              </li>
              
              <li class="menu_tab role">
                <a href="http://developer.guru:8001/grants/role/list">
                      <i class=""></i>
                      <span>Role</span>
                  </a>
              </li>
              
              <li class="menu_tab dashboard">
                <a href="http://developer.guru:8001/grants/dashboard/list">
                      <i class=""></i>
                      <span>Dashboard</span>
                  </a>
              </li>
              
          <li class="">
              <a href="http://developer.guru:8001/grants/Menu/list">
                  <span class="fa fa-plus"></span>
              </a>
             
          </li>
        `;
        return view("components/navigation", ['navItems' => $navItems]);
    }
}