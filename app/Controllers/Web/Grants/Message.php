<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Message extends WebController
{

    protected $messageLib;

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->messageLib=new \App\Libraries\Core\MessageLibrary();

    }
   /**
     * deleteNote(): This method deletes existing messages/notes
     * @author Livingstone Onduso
     * @access public
     * @return  void
     */
    public function deleteNote(){
  
        $response=$this->messageLib->deleteNote();
  
        if((int)$response ==1){
          echo get_phrase('deletion_successful', 'Message Deleted.');
        }else{
          echo get_phrase('deletion_failed', 'Deletion Failed.');
        }
      }
}
