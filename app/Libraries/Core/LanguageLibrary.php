<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\LanguageModel;
class LanguageLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;

    private $language = '';
    private $default_language = '';
    private $account_system_code = 'global';
    private $default_language_path = APPPATH.'language'.DIRECTORY_SEPARATOR;
    private $global_language_path = '';

    function __construct()
    {
        parent::__construct();
        $this->coreModel = new LanguageModel();
        $this->table = 'language';
        $defaultLocale = service('settings')->get('App.defaultLocale');
        $this->default_language = $defaultLocale;
        $this->default_language_path = $this->default_language_path. $defaultLocale . $this->account_system_code.DIRECTORY_SEPARATOR;
        $this->global_language_path = APPPATH.'language'.DIRECTORY_SEPARATOR . $defaultLocale . DIRECTORY_SEPARATOR .'global'.DIRECTORY_SEPARATOR;
    }


    public function languageLocaleById(int $languageId){
        $builder = $this->read_db->table($this->table);
        $builder->select('language_code');
        $builder->where('language_id', $languageId);
        $result = $builder->get()->getRow();
        return $result->language_code;
    }

    function createLanguageFiles($language, $lang_file_group = 'global'){

        if($lang_file_group != "global"){
          $this->default_language_path = APPPATH.'language'.DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR.$lang_file_group.DIRECTORY_SEPARATOR;
        }
    
        if(!file_exists($this->default_language_path)){
          mkdir($this->default_language_path);
        }
        
        if(!file_exists($this->default_language_path.'App.php')){
          $fp = fopen($this->default_language_path.'App.php', 'a');
          fwrite($fp,'<?php '.PHP_EOL);
    
          if($this->account_system_code != 'global' ){
            if (
                  file_exists($this->default_language_path.'App.php') 
                  && file_exists($this->global_language_path.'App.php')
                  && !copy($this->global_language_path.'App.php', $this->default_language_path.'App.php')) 
            {
                log_message('error', 'Coyping language file failed');
            }
          }else{
            session()->set('user_locale', $this->default_language);
            if (!copy($this->global_language_path.$this->default_language.'_lang.php', $this->default_language_path.'App.php')) {
              log_message('error', 'Coyping language file failed');
            }
          }
    
          // Get Language id
          $language_id = $this->getLanguageIdByCode($language);
          // Update the account system language setting
          $accountSystemSettingLibrary = new \App\Libraries\Core\AccountSystemSettingLibrary();
          $accountSystemSettingLibrary->insertNewAccountSystemLanguage($language_id);
    
          // Remove duplicate lines from the lang file
          $this->remove_duplicate_language_keys($this->default_language_path.'App.php');
    
        }
    
        return true;
      }

      function remove_duplicate_language_keys($lang_file_path){
        $lines = file($lang_file_path);
        $unique_lines = array_unique($lines);
        file_put_contents($lang_file_path, implode('', $unique_lines));
      }

      function getLanguageIdByCode($language_code){
        $builder = $this->read_db->table($this->table);
        $builder->select('language_id');
        $builder->where('language_code', $language_code);
        $result = $builder->get()->getRow();
        return $result->language_id;
      }


      function getUserAvailableLanguages(){
        $languages = [];

        // Get all languages 
        $builder = $this->read_db->table($this->table);
        $builder->select('language_id, language_name, language_code');
        $langs_obj = $builder->get();
        
        if($langs_obj->getNumRows() > 0){
            $languagesArray = $langs_obj->getResultArray();

            $i = 0;

            foreach($languagesArray as $language){
                $locale = $language['language_code'];
                $files = APPPATH.'language' . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $this->session->get('user_account_system_code').DIRECTORY_SEPARATOR.'App.php';
                
                if(file_exists($files)){
                    $languages[$i] = $locale;
                }

                $i++;
            }
            
            $langs = [];
            
            if(!empty($languages)){
                
                $builder = $this->read_db->table('language');
                $builder->select(array('language_name','language_code'));
                $builder->whereIn('language_code',$languages);
                $langs_obj = $builder->get();
            
                if($langs_obj->getNumRows() > 0){
                    $langs = $langs_obj->getResultArray();
                }
            }
        }

        return $langs;
      }

      function getDefaultLanguage(): array|null{
        $builder = $this->read_db->table('language');
        $builder->select(array('language_id','language_name','language_code'));
        $builder->where('language_is_default',1);
        $defaultLanguage = $builder->get()->getRowArray();

        return $defaultLanguage;
      }

      function getLanguageById($id){
        $builder = $this->read_db->table($this->table);
        $builder->where('language_id', $id);
        $language = $builder->get()->getRow();
        return $language;
      }

      function getLanguageByCode($languageCode){
        $builder = $this->read_db->table($this->table);
        $builder->where('language_code', $languageCode);
        $language = $builder->get()->getRow();
        return $language;
      }

      function catchLanguagePhrase(int $language_id, int $account_system_id, array $translations){
        $builder = $this->read_db->table('language_phrase');
        $builder->where(array('fk_language_id' => $language_id, 'fk_account_system_id' => $account_system_id));
        $language_phrases_obj = $builder->get();
    
        if($language_phrases_obj->getNumRows() > 0){
          $data['language_phrase_data'] = json_encode($translations);
          $data['language_phrase_last_modified_date'] = date('Y-m-d h:i:s');
          $data['language_phrase_last_modified_by'] = $this->session->user_id;
    
          $builder = $this->write_db->table('language_phrase');
          $builder->where(array('fk_language_id' => $language_id, 'fk_account_system_id' => $account_system_id));
          $builder->update( $data);
        }else{
          $data['fk_account_system_id'] = $account_system_id;
          $data['fk_language_id'] = $language_id;
          $data['language_phrase_data'] = json_encode($translations);
          $data['language_phrase_created_date'] = date('Y-m-d h:i:s');
          $data['language_phrase_created_by'] = $this->session->user_id;
          $data['language_phrase_last_modified_date'] = date('Y-m-d h:i:s');
          $data['language_phrase_last_modified_by'] = $this->session->user_id;

          $builder = $this->write_db->table('language_phrase');
          $builder->insert( $data);
        }
      }
}