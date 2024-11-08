<?php

namespace App\Controllers\Web\Core;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class MenuUserOrder extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function updateFavoriteByAjax(){
        // echo $fav_status;
        
        $menuUserOrderLibrary = new \App\Libraries\Core\MenuUserOrderLibrary();
        $menu_data = $menuUserOrderLibrary->updateFavorite();

        return $this->response->setJSON($menu_data);
      }


      function getFavoriteMenuItems(){
        $items = [];
        $menuLibrary = new \App\Libraries\Core\MenuLibrary();
        if(
           $this->session->data_privacy_consented 
          )
        {
          $items = $menuLibrary->getFavoriteMenuItems();
        }
        
        return $this->response->setJSON($items);
      }
}
