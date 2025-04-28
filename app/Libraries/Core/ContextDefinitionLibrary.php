<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\ContextDefinitionModel;

class ContextDefinitionLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{
    protected $table;

    protected $contextDefinitionModel;

    public function __construct()
    {
        parent::__construct();

        $this->contextDefinitionModel = new ContextDefinitionModel();

        $this->table = 'context_definition';
    }

    public function contextDefinitions()
    {

        $builder = $this->read_db->table($this->table);

        $context_definition = $builder->where('context_definition_is_active', 1)
            ->orderBy('context_definition_level', 'ASC')
            ->get()
            ->getResultArray();

        $order_array = [];

        foreach ($context_definition as $definition) {
            $context_definition_name = $definition['context_definition_name'];
            $context_definition_level = $definition['context_definition_level'];
            $context_definition_id = $definition['context_definition_id'];

            $context_table = "context_" . $context_definition_name;
            $context_user_table = $context_table . '_user';
            $fk = 'fk_' . $context_table . '_id';
            $context_level = $context_definition_level;
            

            $order_array[$context_definition_name] = [
                'context_definition_name' => $context_definition_name,
                'context_definition_id' => $context_definition_id,
                'context_table' => $context_table,
                'context_user_table' => $context_user_table,
                'fk' => $fk,
                'context_definition_level' => $context_level
            ];
        }

        return $order_array;
    }

    function getReportingContextLevels($user_context_level)
    {

        $builder = $this->read_db->table($this->table);
        $builder->select(array('context_definition_name'));
        $builder->orderBy('context_definition_level', 'ASC');
        $builder->where(array('context_definition_level<=' => $user_context_level));
        $hierachy_context = $builder->get()->getResultArray();

        return $hierachy_context;
    }
}