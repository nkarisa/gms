<?php
namespace App\Traits\System;

/**
 * The Extendable trait methods are meant to be implemented or extended in any library that extends the GrantsLibrary
 * The methods are for overriding the default framework implementation.
 */
trait Extendable
{
  /**
   * Controls the visibility of add button. It used to provide an extra visibility control apart from the user permission.
   * 
   * @return bool
   */
  public function showAddButton(): bool
  {
    return true;
  }

  public function showEditButton(): bool
  {
    return true;
  }

  /**
   * Changes the type of a field in an add and edit from
   * 
   * @return array
   */
  public function changeFieldType(): array
  {
    return [];
  }

  /**
   * Control the columns to be displayed in a list view table
   * Make sure that these columns must be part of the database table columns.
   * Id columns should be names is such away that the id is removed and placed with name.
   * fk prefixes should be be removed as well.
   * It overrides the default columns
   * @return array
   */
  public function singleFormAddVisibleColumns(): array
  {
    return [];
  }

  /**
   * Similar as listTableVisibleColumns but lists the columns for detail list view tables
   * It overrides the the default columns
   * Id columns should be names is such away that the id is removed and placed with name.
   * fk prefixes should be be removed as well.
   * @return array
   */
  public function detailListTableVisibleColumns(): array
  {
    return [];
  }
  /**
   * Columns to be displayed in an edit form. It overrides the default columns.
   * Id columns should be names is such away that the id is removed and placed with name.
   * fk prefixes should be be removed as well.
   * @return array
   */
  public function editVisibleColumns(): array
  {
    return [];
  }

  /**
   *  This is an override method that lists the fields that should be displayed in a list view page.
   * Id columns should be names is such away that the id is removed and placed with name.
   * fk prefixes should be be removed as well.
   * @return array
   */
  public function listTableVisibleColumns(): array
  {
    return [];
  }

  /**
   * Turns a select field in an add and edit form to multisect field with select2 plugin.
   * Only one field can be made multselect in an add and edit form. 
   * Id columns should be names is such away that the id is removed and placed with name.
   * fk prefixes should be be removed as well.
   * @return string
   */
  public function multiSelectField(): string
  {
    return '';
  }

  /**
   * Controls options in select fields in an add and edit form
   * 
   * @return array
   */
  function lookupValues(): array
  {
    $lookupValues = [];
    $lookupTables = $this->lookupTables();

    if (!$this->session->system_admin) {
      if (in_array('account_system', $lookupTables)) {
        $accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
        $getAccountSystems = $accountSystemLibrary->getAccountSystems();

        $lookupValues['account_system'] = array_filter($getAccountSystems, function ($accountSystem) {
          $user_account_system_id = $this->session->user_account_system_id;
          if ($accountSystem->account_system_id == $user_account_system_id) {
            return $accountSystem;
          }
        });
      }

      if (in_array('office', $lookupTables)) {
        $officeReadBuilder = $this->read_db->table('office');
        $officeReadBuilder->where([
          'office_is_active' => 1,
          'fk_account_system_id' => $this->session->user_account_system_id,
          'fk_context_definition_id' => 1
        ]);

        $officeObj = $officeReadBuilder->get();
        $offices = [];

        if ($officeObj->getNumRows() > 0) {
          $offices = $officeObj->getResultArray();
        }

        $lookupValues['office'] = $offices;
      }
    }

    return $lookupValues;
  }

  function getUnusedLookupValues($lookUpTableBuilder, &$lookup_values, $lookup_table, $association_table, $not_exist_string_condition = '')
  {

    $lookUpTableBuilder->where('NOT EXISTS (SELECT * FROM ' . $association_table . ' WHERE ' . $association_table . '.fk_' . $lookup_table . '_id=' . $lookup_table . '.' . $lookup_table . '_id ' . $not_exist_string_condition . ')', '', FALSE);

    if ($this->config->dropTransactingOffices) {
      $lookUpTableBuilder->where(array('office_is_readonly' => 0));
    }

    if ($lookup_table == 'office' && !$this->session->system_admin) {
      $hierarchy_offices = array_column($this->session->hierarchy_offices, 'office_id');
      $lookUpTableBuilder->whereIn('office_id', $hierarchy_offices);
    }

    $lookUpTableBuilder->select(array($lookup_table . '_id', $lookup_table . '_name'));
    $lookUpTableBuilder->join($lookup_table, $lookup_table . '.' . $lookup_table . '_id=' . $association_table . '.fk_' . $lookup_table . '_id');
    $lookup_values[$lookup_table] = $lookUpTableBuilder->get()->getResultArray();

    return $lookup_values;
  }


  /**
   * This method is use to format values in a list view.
   * 
   * @param string $columnName
   * @param mixed $columnValue
   * @param array $rowArray
   * @return mixed
   */
  function formatColumnsValues(string $columnName, mixed $columnValue, array $rowArray, array $dependancyData = []): mixed
  {
    return $columnValue;
  }

  /**
   * Lists the tables that are to be details table in a view page.
   * Note that these tables must have foreign key of the master table
   * 
   * @return array
   */
  public function detailTables(): array
  {
    return [];
  }

  /**
   * Draws the select fields in an add and edit from. The name field of these tables should also be part of the columns to be viewed.
   * It works hand in hand with the singleFormAddVisibleColumns and editVisibleColumns.
   * 
   * @return array
   */
  function lookupTables(): array
  {
    return [];
  }

  protected function detachDetailTable(): bool
  {
    return false;
  }

  /**
   * Allows to condition the records displayed in a select field of an add or edit form
   * 
   * @param \CodeIgniter\Database\BaseBuilder $builder
   * @return array
   */
  function lookupValuesWhere(\CodeIgniter\Database\BaseBuilder $builder): array
  {
    return [];
  }

  /**
   * Control the visibility of a list view table edit action above the existing user permission control based on the data
   * in the current record shown in the table 
   * 
   * @param array $record
   * @return bool - True, if the edit button is enabled
   */

  function showListEditAction(array $record, array $dependancyData = []): bool
  {
    return true;
  }

  /**
   * This method provide a means to take an action after a successful approval action
   * 
   * @param array{item: string, post: array} $item 
   * - item: This is the name of the feature/table 
   * - post: Is any array receive fromt he approval request and has the following
   * - item_id: The Id of the item being approved
   * - current_status: The status of the item being approved
   * - next_status: The next status of the item being approved
   * @return void
   */
  public function postApprovalActionEvent(array $item): void
  {

  }

  /**
   * Provides a way to get the database records to be listed in a list page in a customized manner.
   * It ovverides the default framewor implementation of retrieving list records from the database.
   *
   * This method is responsible for fetching the data from the database and preparing it for display in the list table.
   * It takes the provided $datatableBuilder, an array of selected columns $listSelectColumns, and optional parameters $parentId and $parentTable.
   *
   * @param \CodeIgniter\Database\BaseBuilder $datatableBuilder The database builder object used to construct the query.
   * @param array $listSelectColumns An array of column names to be selected from the database.
   * @param string $parentId (Optional) The ID of the parent record, if applicable.
   * @param string $parentTable (Optional) The name of the parent table, if applicable.
   *
   * @return array{result: array} An associative array containing the following key:
   *  - 'results': An array of records to be displayed in the list table.
   */

  public function list(\CodeIgniter\Database\BaseBuilder $datatableBuilder, array $listSelectColumns, string $parentId = null, string $parentTable = null): array
  {

    return ['results' => []];
  }

  /**
   * Allows adding extra columns to a list table. The columns are not necessarily be part of the database table fields.
   * This method normally works hand in hand with formatColumnsValues to define the values of the added columns.
   * Each element has a key of the new column name and a value of the column the new column will ne placed after
   * @return array
   */
  public function additionalListColumns(): array
  {
    return [];
  }

  public function accessAddFormFromMainMenu(): bool
  {
    return true;
  }
  public function detailMultiFormAddVisibleColumns(): array
  {
    return [];
  }
  public function masterMultiFormAddVisibleColumns(): array
  {
    return [];
  }

  public function detailTablesSingleFormAddVisibleColumns(): array
  {
    return [];
  }

  public function defaultFieldValue(): array
  {
    return [];
  }

  public function masterTableAdditionalFields(): array
  {
    return [];
  }

  public function masterView(): array
  {
    return [];
  }

  public function masterTableVisibleColumns(): array
  {
    return [];
  }

  public function detailListTableWhere(\CodeIgniter\Database\BaseBuilder $builder): void
  {

  }

  public function detailListQuery(): void
  {

  }

  function currencyFields()
  {
    return [];
  }

  public function transactionValidateDuplicatesColumns(): array
  {
    return [];
  }

  protected function transactionValidateByComputationFlag(array $arrayToCheck)
  {
    return VALIDATION_SUCCESS; // OR VALIDATION_ERROR
  }

  public function orderListPage(): string
  {
    return ''; // Example - 'status_approval_sequence ASC';
  }

  function customTableJoin(\CodeIgniter\Database\BaseBuilder $builder): void
  {

  }

  /**
   * @return array<string, mixed> // Enforces an associative array
   */
  function formatColumnsValuesDependancyData(array $data): array
  {
    return [];
  }

  /**
   * @return array<string, mixed> // Enforces an associative array
   */
  function showListEditActionDependancyData(array $data): array
  {
    return [];
  }

}