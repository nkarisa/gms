<style>
  .chosen-single {
    min-height: 30px;
}
</style>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default" data-collapsed="0">
                <div class="panel-heading">
                    <div class="panel-title" >
                        <i class="fa fa-toggle-on"></i>
                            <?php echo get_phrase('switch_user');?>
                    </div>
                </div>
            
                <div class="panel-body"  style="max-width:50;height:auto;">	
                    <?php echo form_open(base_url()."login/switch_user" , array('id'=>'frm_switch_user','class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data'));?>
                        <div class="form-group">
                            <label class="control-label col-xs-2"><?=get_phrase('choose_user_to_switch_to');?></label>

                            <div class="col-xs-8">
                                <select name="user_id" id="user_id" class="form-control chosen">
                                    <option value=""><?=get_phrase("choose_a_user");?></option>
                                    <?php 
                                        if($session->has('primary_user_data')){
                                            ?>
                                                <option value="<?=$session->get('primary_user_data')['user_id'];?>"><?=$session->get('primary_user_data')['user_name'].' ['. get_phrase('your_account') .']';?></option>
                                            <?php
                                        }
                                        foreach($result as $user_id => $user_name){
                                            ?>
                                                <option value="<?=$user_id;?>"><?=$user_name;?></option>
                                            <?php
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="col-xs-2">
                                <button id='switch' class="btn btn-success"><?=get_phrase('switch');?></button>
                            </div>
                        </div>
                    </form>
                </div>
        </div>
    </div>
</div>

<script>
    $("#user_id").chosen({
        disable_search_threshold: 10,
        no_results_text: "Oops, nothing found!",
        width: "95%"
    });

    $('#switch').on('click', function(){

        let user_id = $('#user_id').val();

        if(user_id == ""){
            alert('<?=get_phrase('select_a_user','Kindly select a user');?>')
            return false;
        }

    });
</script>