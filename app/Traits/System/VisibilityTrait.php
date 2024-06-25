<?php 

namespace App\Traits\System;

trait VisibilityTrait {

    protected function showAddButton():bool{
      return true;
    }
   
    protected function accessAddFormFromMainMenu():bool{
      return true;
    }
    protected function changeFieldType():array{
      return [];
    }
    protected function detailMultiFormAddVisibleColumns():array {
      return [];
    }
    protected function masterMultiFormAddVisibleColumns():array{
      return [];
    }
    protected function singleFormAddVisibleColumns(): array {
      return [];
    }
    protected function detailListTableVisibleColumns(): array{
      return [];
    }
    protected function editVisibleColumns():array {
      return [];
    }
    protected function listTableVisibleColumns():array{
      return [];
    }
    protected function multiSelectField(): string{
      return '';
    }
}