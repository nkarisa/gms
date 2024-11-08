<?php

use App\Libraries\Core\MenuLibrary;
use App\Libraries\Core\UserLibrary;

$config = config(Config\GrantsConfig::class);
$chunk = array_chunk(session()->user_more_menu,$config->extraMenuItemColumns,true);

$menuLibrary = new MenuLibrary();
$favorite_menu_items_with_max_flag = $menuLibrary->getFavoriteMenuItems();

$favorite_menu_items = $favorite_menu_items_with_max_flag['item_list'];
$max_fav_items_reached = $favorite_menu_items_with_max_flag['max_items_reached'];

// print_r($favorite_menu_items);
?>

<style>
    .fa-star-fav {
        color:orange; 
        display: block; 
    }

    .fa-star {
        font-size:18pt; 
        cursor: pointer;   
    }

    .fa-star-unfav {
        display:none;
    }
</style>

<?php 
    $userLibrary = new UserLibrary();
    if($userLibrary->checkRoleHasPermissions(ucfirst($controller),'update')){

?>
<hr/>

<?php }?>

<div class='row'>
    <div class='col-xs-12'>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th colspan="<?=$config->extraMenuItemColumns;?>"><?=get_phrase('more_menu_items');?></th>
            </tr>
        </thead>
        <tbody>
        <?php  
            $lib = "";
            $menu_icon = "fa fa-bars";
            foreach($chunk as $column){

             ?>
            <tr>
             <?php   
            foreach ($column as $user_menu => $user_menu_item) {
                
                if($userLibrary->checkRoleHasPermissions(ucfirst($user_menu),'read')){
        ?>  
                
                    <td class="items" id="<?=$user_menu;?>">
                    <i class="fa fa-star <?=in_array($user_menu, array_keys($favorite_menu_items)) ? 'fa-star-fav' : 'fa-star-unfav'?>" aria-hidden="true"></i>
                    <a class="<?=in_array($user_menu, array_keys($favorite_menu_items)) ? 'fav-item' : 'unfav-item'?>" href="<?=base_url().strtolower($user_menu);?>/list">
                            <span class="title"><?=get_phrase(strtolower($user_menu));?></span>         
                    </a>
                   </td> 
                   
        <?php
                }
            }
         ?>
            </tr>
         <?php   
        }
        ?>
        </tbody>
        </table>
    </div>
</div>

<script>
    $(".items").mouseenter(function () {
        $(this).find('.fa-star').removeClass('fa-star-unfav');
    });

    $(".items").mouseleave(function () {
        $(this).find('.fa-star').addClass('fa-star-unfav');
    });

    $(".fa-star").on('click', function () {

        let url = "<?=base_url();?>ajax/menu_user_order/updateFavoriteByAjax"
        const star = $(this);
        let max_items_reached = false;
  
        if($(this).hasClass('fa-star-fav')){
            // Already a favorite
            const data = {fav_status: 'fav', item_name: $(this).closest('td').attr('id')};
            $.post(url, data, function (items) {
                create_favorite_menu_items(items.item_list);
                star.removeClass('fa-star-fav');
                star.addClass('fa-star-unfav');
            });
        }else{
            // Not a favorite
            const data = {fav_status: 'unfav', item_name: $(this).closest('td').attr('id')};
            $.post(url, data, function (items) {
                create_favorite_menu_items(items.item_list);
                items.is_favorite ? star.addClass('fa-star-fav') : '';
                if(items.max_items_reached){
                    alert('Maximum number of favorite items has been reached');
                }
            });
            
        }
       
    });
</script>