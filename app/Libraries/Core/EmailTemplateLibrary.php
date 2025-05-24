<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\EmailTemplateModel;
class EmailTemplateLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $emailtemplateModel;

    function __construct()
    {
        parent::__construct();

        $this->emailtemplateModel = new EmailTemplateModel();

        $this->table = 'email_template';
    }

    function logEmail($tags, $template_subject, $template_body, $mail_recipients){

        $formatted_tags = [];

        foreach($tags as $tag => $value){
            $formatted_tags['{'.$tag.'}'] = $value;
        }

        if(!empty($formatted_tags)){
            // Replace tags into the template body
            $tag_keys = array_keys($formatted_tags);
            $tag_values = array_values($formatted_tags);

            // Assign values to the class properties (msg, sub, to)
            $msg = str_replace($tag_keys,$tag_values,$template_body);
            $sub = str_replace($tag_keys,$tag_values,$template_subject);

            // Log the mail to be sent in the mail log table. This email will await for a cron job to trigger to have it sent
            $email_template_history_fields = $this->generateItemTrackNumberAndName('mail_log');
            $data['mail_log_name'] = $email_template_history_fields['mail_log_track_number'];
            $data['mail_log_track_number'] = $email_template_history_fields['mail_log_track_number'];
            $data['mail_log_recipients'] = json_encode($mail_recipients);
            $data['mail_log_message'] =  '{"subject":"'.$sub.'","body":"'.$msg.'"}';
            $data['mail_log_created_date'] = date('Y-m-d');
            $data['mail_log_created_by'] = $this->session->user_id ?? 1;
            $data['mail_log_last_modified_by'] = $this->session->user_id ?? 1;
            $data['fk_status_id'] = $this->initialItemStatus('mail_log');;
           
            $this->write_db->table("mail_log")->insert($data);
        }

        return $tags;
    }
   
}