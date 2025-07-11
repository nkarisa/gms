<?php

namespace App\Controllers\Web\Core;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Language extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }


    public function switchLanguage(string $lang): void
    {

        $language = $this->read_db->table('language')
            ->where('language_code', $lang)
            ->get()
            ->getRow();

        $message = "You have not changed your language";

        if ($this->session->get('user_locale') !== $lang && $language) {
            // Update user's language preference
            $data = ['fk_language_id' => $language->language_id];
            $this->write_db->table('user')
                ->where('user_id', $this->session->get('user_id'))
                ->update($data);

            $languageId = $language->language_id;
            $userAccountSystemId = $this->session->get('user_account_system_id');

            // Check if the language is available in the account system language
            $accountSystemLanguageCount = $this->read_db->table('account_system_language')
                ->where('fk_language_id', $languageId)
                ->where('fk_account_system_id', $userAccountSystemId)
                ->countAllResults();

            if ($accountSystemLanguageCount == 0) {
                // Insert the new language entry into account system language table
                $generatedData = $this->libs->generateItemTrackNumberAndName('account_system_language');

                $insertData = [
                    'account_system_language_name' => $generatedData['account_system_language_name'],
                    'account_system_language_track_number' => $generatedData['account_system_language_track_number'],
                    'fk_account_system_id' => $userAccountSystemId,
                    'fk_language_id' => $languageId,
                    'account_system_language_created_date' => date('Y-m-d'),
                    'account_system_language_created_by' => $this->session->get('user_id'),
                    'account_system_language_last_modified_by' => $this->session->get('user_id'),
                    'fk_status_id' => $this->libs->initialItemStatus('voucher')
                ];

                $this->write_db->table('account_system_language')->insert($insertData);
            }

            $message = "Your language has been switched successfully";
            $this->session->set('user_locale', $lang);
        }

        echo get_phrase('language_switch_alert', $message);
    }

    function result($id = 0, $parentTable = null)
    {
        $result = parent::result($id);
        
        $accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
        
        if ($this->action == 'view') {
            $result['master']['account_system_code'] = $this->session->user_account_system_code;
            $result['master']['language_code'] =$this->library->getLanguageById(hash_id($this->id, 'decode'))->language_code;
            $result['master']['account_systems'] = $accountSystemLibrary->getAccountSystems();
        }

        return $result;
    }

    function downloadLanguageFile($account_system_code, $language_code)
    {
        $path = APPPATH . "language" . DIRECTORY_SEPARATOR . $language_code . DIRECTORY_SEPARATOR . $account_system_code . DIRECTORY_SEPARATOR . "App.php";
        if (!file_exists($path)) {
            $path = APPPATH . "language" . DIRECTORY_SEPARATOR . $language_code . DIRECTORY_SEPARATOR . 'global' . DIRECTORY_SEPARATOR . "App.php";
        }

        // $file_contents = file_get_contents($path);
        // Extract the array from the file contents
        // $dBLanguagePhrases = $this->extract_lang_array($file_contents);
        include $path;

        // Convert the $lang array to a CSV string
        $csv_string = $this->array_to_csv($lang);

        // Load the helper and set headers for download
        helper('download');
        return $this->response->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $language_code . "_" . $account_system_code . '_lang.csv"')
            ->setBody($csv_string)
            ->send();
    }

    // private function extract_lang_array($file_contents)
    // {
    //     $lang = [];
    //     eval ('' . $file_contents);
    //     return $lang;
    // }

    private function array_to_csv($array)
    {
        // Create a file pointer
        $fp = fopen('php://temp', 'w+');
        // Write the header row
        fputcsv($fp, ['Key', 'Value']);
        // Write the array to the file pointer in CSV format
        foreach ($array as $key => $value) {
            fputcsv($fp, [$key, $value]);
        }
        // Rewind the file pointer
        rewind($fp);
        // Read the entire file content
        $csv_string = stream_get_contents($fp);
        // Close the file pointer
        fclose($fp);
        return $csv_string;
    }

    public function uploadLanguageFile()
    {
        $accountSystemCode = $this->request->getPost('account_system_code');
        $languageCode = $this->request->getPost('language_code');
        $accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
        $languageLibrary = new \App\Libraries\Core\LanguageLibrary();

        helper(['form']);
      
        if ($this->request->getMethod() == 'POST' && $file = $this->request->getFile('csv_file')) {
            if ($file->isValid() && !$file->hasMoved()) {
                // Check file extension
                if ($file->getExtension() !== 'csv') {
                    log_message('error', "Invalid file format. Please upload a CSV file.");
                    return;
                }

                // Define the target path for the uploaded file
                $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . $languageCode . DIRECTORY_SEPARATOR . $accountSystemCode . DIRECTORY_SEPARATOR;
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, recursive: true);
                }

                $targetFile = $uploadPath . $file->getName();

                // Move the uploaded file to the target path
                if ($file->move($uploadPath, $file->getName(), true)) {
                    // Convert CSV to array
                    $csvArray = $this->csvToArray($targetFile);

                    // Get account system ID and language ID
                    $userAccountSystemId = $accountSystemLibrary->getAccountSystemIdByCode($accountSystemCode);
                    $languageId = $languageLibrary->getLanguageByCode($languageCode)->language_id;

                    // Save data to database and PHP file
                    $languageLibrary->catchLanguagePhrase($languageId, $userAccountSystemId, $csvArray);

                    $languagePath = APPPATH . 'language' . DIRECTORY_SEPARATOR . $languageCode . DIRECTORY_SEPARATOR . $accountSystemCode . DIRECTORY_SEPARATOR;

                    if(!file_exists($languagePath)){
                        mkdir($languagePath, 0777, true);
                    }
                    
                    $this->saveArrayToPhpFile($csvArray, $languagePath . 'App.php');

                    // Remove the CSV file
                    unlink($targetFile);

                    log_message('info', "File uploaded and processed successfully!");
                } else {
                    log_message('error', "There was an error moving your file.");
                }
            } else {
                log_message('error', "There was an error with the uploaded file.");
            }
        } else {
            log_message('error', "No file was uploaded or an error occurred.");
        }

        return redirect()->to(base_url('language/list'));
    }

    private function saveArrayToPhpFile($array, $file_path) {
        $php_code = "<?php\n\n";
        foreach ($array as $key => $value) {
            $php_code .= "\$lang['$key'] = \"" . addslashes($value) . "\";\n";
        }

        file_put_contents($file_path, $php_code);
    }

    private function csvToArray($file_path) {
        $array = [];
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            $header = fgetcsv($handle, 1000, ','); // Skip the first row (header)
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $array[$data[0]] = $data[1];
            }
            fclose($handle);
        }
        
        return $array;
      }
      

}
