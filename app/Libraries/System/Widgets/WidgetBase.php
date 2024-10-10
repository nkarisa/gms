<?php

namespace App\Libraries\System\Widgets;

// use App\Libraries\System\Outputs\OutputBase;

class WidgetBase {
    
    public static $output = null;

    function __construct(){
        // parent::__construct();
    }

    static function load($widget,...$args){
        //Widget example: Comment

        $widget_output_class = ucfirst($widget).'Output';
        $widgetClass = "App\\Libraries\\System\\Widgets\\$widget_output_class";
        $class_exists = false;

        if(class_exists($widgetClass)){
            $widgetObj = new $widgetClass();
            $class_exists = true;

            if(method_exists($widgetObj, 'output')){
                return $widgetObj->output(...$args);
            }else{
                throw new \BadMethodCallException("Method output not found in class '" . $widget_output_class . "'");
            }
        }

        if(!$class_exists){
            throw new \Exception("Class '" . $widget_output_class . "' not found in all library namespaces");
        }
        

    }
}