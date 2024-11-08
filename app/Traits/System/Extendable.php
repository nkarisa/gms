<?php 
namespace App\Traits\System;
trait Extendable {
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
  
      public function detailListTableWhere(\CodeIgniter\Database\BaseBuilder $builder):void {
        
      }
  
  
      public function ListTableWhere(): void{
  
      }

  
      public function detailListQuery(): void {

      }
  
      function lookupValues(): array{
        return [];
      }
  
      function formatColumnsValues(string $columnName, mixed $columnValue): mixed{
        return $columnValue;
      }

      public function detailTables(): array
      {
          return [];
      }
  
      function lookupTables(): array{
          return [];
      }
  
      protected function detachDetailTable(): bool
      {
          return false;
      }
  
      function lookupValuesWhere(\CodeIgniter\Database\BaseBuilder $builder): array
      {
          return [];
      }

      function currencyFields(){
        return [];
    }

    function showListEditAction(array $record): bool{
        return true;
    }

    public function postApprovalActionEvent(array $item): void{

    }
}