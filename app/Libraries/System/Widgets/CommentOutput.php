<?php 

namespace App\Libraries\System\Widgets;

use App\Libraries\System\Outputs\OutputTemplate;

class CommentOutput extends OutputTemplate{

    function __construct(){
        parent::__construct();
        // Class property initialization
    }

    function output(...$args){

        ob_start();
        $item_id = hash_id($this->id,'decode');
        $controller = $this->controller;
        $this->get_chat_messages();
        include VIEWPATH."components/chat.php";
        $output = ob_get_contents();
        ob_end_clean();
        
        return $output;
    }

    function get_chat_messages(){
        $messageLibrary = new \App\Libraries\Core\MessageLibrary();
        return $messageLibrary->getChatMessages($this->controller,hash_id($this->id,'decode'));
    }

}
