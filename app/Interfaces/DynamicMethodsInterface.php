<?php

namespace App\Interfaces;

interface DynamicMethodsInterface
{

    public function post_approval_action_event();
    public function modify_datatable_columns();
    public function list_table_where();
    public function lookup_tables();
    public function detach_detail_table();
    public function access_add_form_from_main_menu();
    public function change_field_type();
    public function detail_multi_form_add_visible_columns();
    public function master_multi_form_add_visible_columns();
    public function single_form_add_visible_columns();
    public function detail_list_table_visible_columns();
    public function edit_visible_columns();
    public function show_add_button();
    public function add();
    public function action_before_insert();
    public function action_before_edit();
    public function action_after_insert();
    public function action_after_edit();
    public function lookup_values_where();
    public function lookup_values();
    public function multi_select_field();
    public function transaction_validate_by_computation_flag();
    public function transaction_validate_duplicates_columns();
    public function intialize_table();
    public function order_list_page();
    public function master_view_table_where();
    public function detail_tables_single_form_add_visible_columns();
    public function default_field_value();
    public function detail_list_query();
    public function master_view();
    public function currency_fields();
    public function master_table_additional_fields();
    public function page_position();
    public function list_table_visible_columns();
}