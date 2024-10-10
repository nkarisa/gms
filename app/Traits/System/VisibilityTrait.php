<?php 

namespace App\Traits\System;

trait VisibilityTrait {

  public function showAddButton():bool{
      return true;
    }
   
    public function accessAddFormFromMainMenu():bool{
      return true;
    }
    public function changeFieldType():array{
      return [];
    }
    public function detailMultiFormAddVisibleColumns():array {
      return [];
    }
    public function masterMultiFormAddVisibleColumns():array{
      return [];
    }
    public function singleFormAddVisibleColumns(): array {
      return [];
    }

    public function detailTablesSingleFormAddVisibleColumns(): array{
      return [];
    }

    public function detailListTableVisibleColumns(): array{
      return [];
    }
    public function editVisibleColumns():array {
      return [];
    }
    public function listTableVisibleColumns():array{
      return [];
    }
    public function multiSelectField(): string{
      return '';
    }

    public function defaultFieldValue(): array{
      return [];
    }

    public function masterTableAdditionalFields(): array {
      return [];
    }

    public function masterView():array {
      return [];
    }

    public function masterTableVisibleColumns():array {
      return [];
    }

    public function detailListTableWhere():array {
      return [];
    }

    public function orderListPage(){
      return '';
    }

    public function detailListQuery(): array {
      return [];
    }

    function lookupValues(): array{
      return [];
    }


}