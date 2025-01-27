<?php if($controller == 'budget_limit'){?>
<div class = "row">
    <div class = "col-xs-12" style = "text-align:left;">
        <a href="<?=base_url();?>budget/view/<?=hash_id($this->id, 'encode');?>"><span class = 'fa fa-arrow-left'> <?=get_phrase('back_to_budget');?></span></a>
    </div>
</div>
<?php }?>

<div class = "row">
    <div class = "col-xs-12">
        <table class = "table table-striped">
            <thead>
                <tr>
                    <th colspan = "6" style="text-align: center;font-weight: bold;"><?=get_phrase('budget_limit_header','Budget Limit List');?></th>
                </tr>
                <tr>
                    <th><?=get_phrase('action');?></th>
                    <th><?=get_phrase('office_code','Office Code');?></th>
                    <th><?=get_phrase('budget_year','Budget Year');?></th>
                    <th><?=get_phrase('budget_review_period','Budget Review Period');?></th>
                    <th><?=get_phrase('income_account_name','Income Accoount Name');?></th>
                    <th><?=get_phrase('budget_limit_amount','Budget Limit Amount');?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                //log_message('error',json_encode($data));
                $userLib=new \App\Libraries\Core\UserLibrary();
            
                $has_budget_limit_edit_permission =$userLib->checkRoleHasPermissions('Budget_limit', 'update');
                foreach($budget_limit as $row) {?>
                    <tr style="text-align: left;">
                        <td><a class = "btn btn-danger <?=!$has_budget_limit_edit_permission ? 'disabled' : ''?>" href="<?=base_url();?>budget_limit/edit/<?=hash_id($row['budget_limit_id'],'encode');?>"><?=get_phrase('edit');;?></a></td>
                        <td><?=$row['office_code'];?></td>
                        <td><?=$row['budget_year'];?></td>
                        <td><?=$row['budget_tag_name'];?></td>
                        <td><?=$row['income_account_name'];?></td>
                        <td><?=number_format($row['budget_limit_amount'],2);?></td>
                    </tr>
                <?php }?>
            </tbody>
        </table>
    </div>
</div>

