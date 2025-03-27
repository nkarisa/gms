<?php 

namespace App\Libraries\System;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\S3\MultipartUploader;
use CodeIgniter\HTTP\Files\UploadedFile;

class AwsAttachmentLibrary {

    protected $attachment_table_name;
    protected $write_db;
    protected $read_db;
    protected $attachment_key_column;
    private $config;
    private $s3Client;
    private $db;

    public function __construct()
    {
        // Load configuration
        $this->config = config(\Config\GrantsConfig::class);

        // Set up the S3
        $this->s3Setup();

        // Access config items directly
        $this->attachment_table_name = service("settings")->get("GrantsConfig.attachment_table_name");
        $this->attachment_key_column = service("settings")->get("GrantsConfig.attachment_key_column");

        $this->db = \Config\Database::connect();
    }

    protected function s3Setup()
    {

        // Array for server credentials
        $s3ClientCredentials = [
            'region' => service("settings")->get("GrantsConfig.s3_region"),
            'version' => '2006-03-01',
        ];

        // Check if running on localhost
        if (!is_cli() && parse_url(base_url())['host'] === 'localhost') {
            $s3ClientCredentials['profile'] = 'default';
        }

        // Instantiate the S3 client
        $this->s3Client = new S3Client($s3ClientCredentials);
    }

    function uploadS3Object($SourceFile, $s3_path, $file_name)
    {
   
      $key = $s3_path . '/' . $file_name;
   
      try {
   
        $this->s3Client->putObject([
          'Key' => $key, // Where the file will be placed in S3
           'Bucket' => $this->config->s3_bucket_name,
          'SourceFile' => $SourceFile, // Where the file originate in the local machine
        ]);
   
        gc_collect_cycles();
      } catch (S3Exception $s3Ex) {
   
        die("An exception occured. {$s3Ex}");
      }
   
      return [$SourceFile];
    }


    function s3PreassignedUrl($object_key)
    {
      $cmd = $this->s3Client->getCommand('GetObject', [
        'Bucket' => $this->config->s3_bucket_name,
        'Key' => $object_key,
   
      ]);
      $request = $this->s3Client->createPresignedRequest($cmd, '+20 minutes');
   
      $presignedUrl = (string)$request->getUri();
   
      return $presignedUrl;
    }

    public function s3_multi_part_upload(array $file_parts, int $country_id): string
    {   
      $file = $file_parts[0] . '_' . $country_id . '.' . $file_parts[1];
   
      $bucket = $this->config->s3_bucket_name; //'participants-csv-upload';
   
      $keyname = 'reimbursement_participants/' . $file; // Replace with your desired file path and name in S3
   
      $csv_source = $_FILES['file']['tmp_name'];
   
      //Upload to S3 using Multi-part
      $uploader = new MultipartUploader($this->s3Client, $csv_source, [
        'bucket' => $bucket,
        'key' => $keyname,
        'multipart_upload' => [
          'chunk_size' => 1 * 1024 * 1024 // Set the chunk size (1 MB in this example)
        ]
      ]);
   
      // Upload the file to S3 using multipart upload
      try {
        $result = $uploader->upload();
   
        return get_phrase("success_upload_msg", "File Uploaded Successfully & Will take [10 minutes] to synchronize participants");
      } catch (\Exception $e) {
        return "Error uploading file: " . $e->getMessage();
      }
    }

    public function attachmentRecordWithS3PreassignedUrl(array $attachmentWhereConditions = [])
    {
       
        
        $builder = $this->db->table($this->attachment_table_name);
        $attachmentObj = $builder->where($attachmentWhereConditions)->get()->getResultArray();
        $result = [];
        if (!$attachmentObj) {
            return $result;
        }
        for ($i=0; $i < sizeof($attachmentObj); $i++) { 
            $objectKey = $attachmentObj[$i]['attachment_url'] . '/' . $attachmentObj[$i]['attachment_name'];
            $s3PreassignedUrl = $this->s3PreassignedUrl($objectKey);
            array_push($result, [
                'attachment_name' => $attachmentObj[$i]['attachment_name'],
                'attachment_size' => formatBytes($attachmentObj[$i]['attachment_size']),
                'attachment_last_modified_date' => $attachmentObj[$i]['attachment_last_modified_date'],
                'attachment_file_type' => $attachmentObj[$i]['attachment_file_type'],
                'attachment_primary_id' => $attachmentObj[$i]['attachment_primary_id'],
                'attachment_id' => $attachmentObj[$i]['attachment_id'],
                's3_preassigned_url' => $s3PreassignedUrl
            ]);
        }
        return $result;
        // $objectKey = $attachmentObj->attachment_url . '/' . $attachmentObj->attachment_name;
        // $s3PreassignedUrl = $this->s3PreassignedUrl($objectKey);

        // return [
        //     'attachment_name' => $attachmentObj->attachment_name,
        //     'attachment_size' => formatBytes($attachmentObj->attachment_size),
        //     'attachment_last_modified_date' => $attachmentObj->attachment_last_modified_date,
        //     'attachment_file_type' => $attachmentObj->attachment_file_type,
        //     'attachment_primary_id' => $attachmentObj->attachment_primary_id,
        //     'attachment_id' => $attachmentObj->attachment_id,
        //     's3_preassigned_url' => $s3PreassignedUrl
        // ];
    }

    public function uploadSingleFileFromDirectory(
        string $storeFolder,
        string $fileSourcePath,
        UploadedFile $file,
        string $customFileName,
        array $additionalAttachmentTableInsertData = [],
        array $attachmentWhereConditionArray = []
    ): array {
        $preassignedUrls = [];

        if (file_exists($fileSourcePath)) {
            $this->uploadS3Object($fileSourcePath, $storeFolder, $customFileName);

            $builder = $this->db->table($this->attachment_table_name);
            $builder->where(['attachment_name' => $customFileName]);

            if (!empty($attachmentWhereConditionArray)) {
                $builder->where($attachmentWhereConditionArray);
            }

            $fileExists = $builder->countAllResults();

            if (!$fileExists) {
                $attachmentData = [
                    'attachment_name' => $customFileName,
                    'attachment_size' => $file->getSize(),
                    'attachment_track_number' => model('GrantsModel')->generate_item_track_number_and_name('attachment')['attachment_track_number'],
                    'attachment_file_type' => $file->getMimeType(),
                    'attachment_url' => $storeFolder,
                    'fk_account_system_id' => session()->get('user_account_system_id'),
                ];

                if (!empty($additionalAttachmentTableInsertData)) {
                    $attachmentData = array_merge($attachmentData, $additionalAttachmentTableInsertData);
                }

                $this->db->table($this->attachment_table_name)->insert($attachmentData);
            }
        }

        return $preassignedUrls;
    }

    public function uploadFilesDirectly(string $storeFolder): void
    {
        // Retrieve uploaded files
    
        $files = service('request')->getFiles();

        if (!empty($files) && isset($files['file'])) {
            $uploadedFiles = $files['file'];

            foreach ($uploadedFiles as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $tempFile = $file->getTempName();
                    $originalName = $file->getClientName();

                    // Upload file to S3
                    $this->uploadS3Object($tempFile, $storeFolder, $originalName);
                }
            }
        }
    }


    public function uploadFiles(string $storeFolder, array $additionalAttachmentTableInsertData, array $attachmentWhereConditionArray = []): array
    {
        $preassignedUrls = [];
        $files = service('request')->getFiles();

        log_message('error', json_encode($files));

        if (!empty($files) && isset($files['file'])) {
            $uploadedFiles = $files['file'];

            foreach ($uploadedFiles as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $tempFile = $file->getTempName();
                    $originalName = $file->getClientName();
                    $stripedFileName = preg_replace('/[^a-zA-Z0-9.]+/', '-', $originalName);

                    // S3 Upload
                    $this->uploadS3Object($tempFile, $storeFolder, $stripedFileName);

                    // Prepare attachment data
                    $attachmentData = [
                        'attachment_name' => $stripedFileName,
                        'attachment_size' => $file->getSize(),
                        'attachment_file_type' => $file->getMimeType(),
                        'attachment_url' => $storeFolder,
                        'fk_account_system_id' => session()->get('user_account_system_id'),
                    ];

                    if (!empty($additionalAttachmentTableInsertData)) {
                        $attachmentData = array_merge($attachmentData, $additionalAttachmentTableInsertData);
                    }

                    // Insert data into the database
                    
                    $this->db->table($this->attachment_table_name)->insert($attachmentData);
                    $lastId = $this->db->insertID();

                    // Check specific folders and perform updates if needed
                    if (preg_match('/receipts/', $storeFolder) || preg_match('/support_documents/', $storeFolder)) {
                        // model('MedicalClaimModel')->update_medical_claim_attachment_id($lastId);
                        $medicalClaimLibrary = new \App\Libraries\Grants\MedicalClaimLibrary();
                        $medicalClaimLibrary->updateMedicalClaimAttachmentId();
                    }

                    // Generate preassigned URLs for each file
                    $preassignedUrls[$originalName] = $this->attachmentRecordWithS3PreassignedUrl($attachmentWhereConditionArray);
                }
            }
        }
 
        return $preassignedUrls;
    }

    public function retrieveFileUploadsInfo(array $attachmentWhereConditionArray): array
    {

        $filesArray = [];

        if (!empty($attachmentWhereConditionArray['attachment_primary_id'])) {
            $builder = $this->db->table($this->attachment_table_name);

            foreach ($attachmentWhereConditionArray as $key => $value) {
                if (is_array($value)) {
                    $builder->whereIn($key, $value);
                } else {
                    $builder->where($key, $value);
                }
            }

            $query = $builder->get();
            if ($query->getNumRows() > 0) {
                $filesArray = $query->getResultArray();
            }
        }

        return $filesArray;
    }
  
    function getLocalFilesystemAttachmentUrl($objectKey){
        return base_url().$objectKey;
      }

    public function deleteBankStatementInS3(string $file_name){

        try {
      
            $this->s3Client->deleteObject([
                'Key' => $file_name,
                'Bucket' => $this->config->s3_bucket_name,
            ]);
      
            //Remove the temp files after gabbage collection for the S3 guzzlehttp to release resources
      
            gc_collect_cycles();
      
      
        } catch (S3Exception $s3Ex) {
      
            die("An exception occured. {$s3Ex}");
        }
      
       }
}