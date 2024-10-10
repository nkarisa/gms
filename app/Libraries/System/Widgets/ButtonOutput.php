<?php
namespace App\Libraries\System\Widgets;

use App\Libraries\System\Outputs\OutputTemplate;
class ButtonOutput extends OutputTemplate{
    
    function __construct(){
        parent::__construct();
    }

    function output(...$args){
        $label = $args[0]??get_phrase('default_button');;
        $action = $args[1]??"";;
        $widget_id = $args[2]??"";;
        $additional_class = $args[3]??"";
        $onclick=$args[4]??"";
        
        $action = $action == "" || $action == "#" ? "#" : base_url().ucfirst($action);

        return '
            <a href="'.$action.'" class="btn btn-default '.$additional_class.'" id="'.$widget_id.'" onClick="'. $onclick.'">'
            .ucfirst($label).
            '</a>
        ';
    }
}


