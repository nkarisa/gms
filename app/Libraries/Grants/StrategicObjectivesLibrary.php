<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\StrategicObjectivesModel;
class StrategicObjectivesLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new StrategicObjectivesModel();

        $this->table = 'grants';
    }


    function loadStrategicObjectivesCostingView($budget_id)
    {


        $data['data'] = $this->getStrategicObjectivesCosting($budget_id);

        $strategic_objectives_costing_view = view('strategic_objectives/strategic_objectives_costing', $data);

        // log_message('error', json_encode($budget_limit_view));

        return $strategic_objectives_costing_view;
    }

    private function getStrategicObjectivesCosting(float $budget_id):array
    {
        // Query Builder
        $builder = $this->read_db->table('budget_item');
        $builder->select([
            'budget_item_id',
            'budget_item_objective'
        ]);
        $builder->selectSum('budget_item_detail_amount');
        $builder->where('fk_budget_id', $budget_id);
        $builder->join('budget_item_detail', 'budget_item_detail.fk_budget_item_id = budget_item.budget_item_id');
        $builder->groupBy('budget_item_id');

        // Execute the query
        $budget_items_with_objectives_obj = $builder->get();

        $budget_items_with_objectives = [];
        $summaries = ['tabulation' => [], 'tallies' => []];

        $count_with_objectives = 0;
        $count_without_objectives = 0;
        $amount_with_objectives = 0;
        $amount_without_objectives = 0;

        if ($budget_items_with_objectives_obj->getNumRows() > 0) {
            $budget_items_with_objectives_array = $budget_items_with_objectives_obj->getResultArray();

            foreach ($budget_items_with_objectives_array as $budget_item) {
                if ($budget_item['budget_item_objective'] == NULL) {
                    $count_without_objectives++;
                    $amount_without_objectives += $budget_item['budget_item_detail_amount'];
                    continue;
                } else {
                    $amount_with_objectives += $budget_item['budget_item_detail_amount'];
                    $count_with_objectives++;
                }

                $objective = json_decode($budget_item['budget_item_objective']);
                $budget_items_with_objectives[$budget_item['budget_item_id']] =  [
                    'objective_id' => $objective->pca_strategy_objective_id,
                    'objective_name' => $objective->pca_strategy_objective_name,
                    'intervention_id'  => $objective->pca_strategy_intervention_id,
                    'intervention_name'  => $objective->pca_strategy_intervention_name,
                    'budget_item_amount' => $budget_item['budget_item_detail_amount']
                ];
            }

            foreach ($budget_items_with_objectives as $budget_item_id => $summary_item) {
                $summaries['tabulation']['objectives_summary'][$summary_item['objective_id']][] = ['name' => $summary_item['objective_name'], 'amount' => $summary_item['budget_item_amount']];
                $summaries['tabulation']['interventions_summary'][$summary_item['intervention_id']][] = ['name' => $summary_item['intervention_name'], 'amount' => $summary_item['budget_item_amount']];
            }
        }

        $summaries['tallies']['with_objectives']['count'] = $count_with_objectives;
        $summaries['tallies']['without_objectives']['count'] = $count_without_objectives;
        $summaries['tallies']['with_objectives']['amount'] = $amount_with_objectives;
        $summaries['tallies']['without_objectives']['amount'] = $amount_without_objectives;

        return $summaries;
    }
}
