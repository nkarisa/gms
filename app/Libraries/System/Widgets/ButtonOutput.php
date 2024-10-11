<?php
namespace App\Libraries\System\Widgets;

use App\Libraries\System\Outputs\OutputTemplate;
class ButtonOutput extends OutputTemplate{
    
    function __construct(){
        parent::__construct();
    }

    function output(...$args){
        $label = isset($args[0]) ? $args[0]  : get_phrase('default_button');;
        $action = isset($args[1]) ? $args[1] : "";
        $widget_id = isset($args[2]) ? $args[2] : "";
        $additional_class = isset($args[3]) ? $args[3] : "";
        $onclick = isset($args[4]) ? $args[4] : "";
        
        $action = $action == "" || $action == "#" ? "#" : site_url(strtolower($action));

        return '
            <a href="'.$action.'" class="btn btn-default '.$additional_class.'" id="'.$widget_id.'" onClick="'. $onclick.'">'
            .ucfirst($label).
            '</a>
        ';
    }
}


