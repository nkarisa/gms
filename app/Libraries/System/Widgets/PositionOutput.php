<?php

namespace App\Libraries\System\Widgets;

use App\Libraries\System\Outputs\OutputTemplate;

class PositionOutput extends OutputTemplate{
    
    function __construct(){
        parent::__construct();
    }

    function output(...$args){
        
        $position_title = $args[0];

        $page_action = isset($args[1])?$args[1]:$this->action;

        $lib = $this->currentLibrary;

        $page_positions = null;
        
        if(method_exists($lib,'pagePosition')){
           $page_positions = $lib->pagePosition();
        }
        
        if(is_array($page_positions) && array_key_exists($position_title,$page_positions)){
            if(array_key_exists($page_action,$page_positions[$position_title])){
                if(is_array($page_positions[$position_title][$page_action])){
                    return implode(" ",$page_positions[$position_title][$page_action]);
                }else{
                    return $page_positions[$position_title][$page_action];
                }
            }elseif(!is_array($page_positions[$position_title])){
                return $page_positions[$position_title];
            }else{
                return null;
            }
        }else{
            return null;//'<i>Page position key "'.$position_title.'" in not defined in "'.$lib.'"!</i>';
        }
        
    }
}
