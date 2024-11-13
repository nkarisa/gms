<?php 

namespace App\Traits\System;


trait DataTable {
    function setDatatableLimit(\CodeIgniter\Database\BaseBuilder $builder){
        $start = intval($this->request->getPost('start'));
        $length = intval($this->request->getPost('length'));

        $builder->limit($length, $start);

        return $builder;
    }

    function setDatatableOrdering(\CodeIgniter\Database\BaseBuilder $builder, string $tableName, array $selectColumns){
        $order = $this->request->getPost('order');
        $col = '';
        $dir = 'desc';

        if (!empty($order)) {
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
        }

        if ($col == '') {
            $builder->orderBy($tableName.'_id DESC');
        } else {
            $builder->orderBy($selectColumns[$col], $dir);
        }

        return $builder;
    }

    function setDatatableSearching(\CodeIgniter\Database\BaseBuilder $builder, array $selectColumns){
        $search = $this->request->getPost('search');
        $value = isset($search['value']) ? $search['value'] : '';

        array_pop($selectColumns);

        if (!empty($value)) {
            $builder->groupStart();
            $column_key = 0;
            foreach ($selectColumns as $column) {
                if ($column_key == 0) {
                    $builder->like($column, $value, 'both');
                } else {
                    $builder->oRlike($column, $value, 'both');
                }
                $column_key++;
            }
            $builder->groupEnd();
        }

        $post = $this->request->getPost();
        $customFields = [];

        foreach($post as $key => $value) {
            if (str_starts_with($key, "customfield_")) {
                $customFields[substr($key, 12)] = $value;
            }
        }

        if(!empty($customFields)) {
            $this->checkDataTableCondition($builder, $customFields);
        }
        
        return $builder;
    }

    protected function dataTableCondition(\CodeIgniter\Database\BaseBuilder $builder, array $dataFields){
        //$builder->where($dataFields);
    }

    function dataTableBuilder(\CodeIgniter\Database\BaseBuilder &$builder, string $tableName, array $selectColumns){
        // log_message('error', json_encode($this->request->getPost()));
        if ($this->request->getPost('draw')) {
            // Limiting Server Datatable Results
            $this->setDatatableLimit($builder);
            // Ordering Server Datatable Results
            $this->setDatatableOrdering($builder, $tableName, $selectColumns);
            // Search records
            $this->setDatatableSearching($builder, $selectColumns);
        } 
        
        return $builder;
    }
}