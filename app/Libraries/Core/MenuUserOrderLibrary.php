<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\MenuUserOrderModel;
class MenuUserOrderLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new MenuUserOrderModel();

        $this->table = 'menu_user_order';
    }


    function updateFavorite(): array {

        $post = $this->request->getPost();
 
        $fav_status = $post['fav_status'];
        $item_name = $post['item_name'];
    
        $builder = $this->read_db->table('menu_user_order');
        $builder->where(['fk_user_id' => $this->session->user_id, 'menu_user_order_is_favorite' => 1]);
        $count_of_favorites = $builder->get()->getNumRows();
    
        $builder = $this->read_db->table('menu');
        $builder->where(array('menu_name' => $item_name));
        $menu = $builder->get()->getRow();
    
        $is_favorite = 0;
    
        if($fav_status == 'unfav' && $count_of_favorites < $this->config->max_count_of_favorites_menu_items){
          $is_favorite = 1;
        }
    
        $data = ['menu_user_order_is_favorite' => $is_favorite];
        $builder = $this->write_db->table('menu_user_order');
        $builder->where(['fk_menu_id' => $menu->menu_id, 'fk_user_id' => $this->session->user_id]);
        $builder->update( $data);
    
        $menuLibrary = new \App\Libraries\Core\MenuLibrary();
        $menu_data = $menuLibrary->getFavoriteMenuItems();
        $menu_data['is_favorite'] =  $is_favorite;
      
        return $menu_data;
    }
   
}