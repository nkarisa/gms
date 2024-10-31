<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Beneficiary extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    public function uploadLargeCsvDataToS3(): void
{
    // Load the AWS attachment library
    $awsAttachmentLibrary = new \App\Libraries\System\AwsAttachmentLibrary();

    // Retrieve uploaded file
    $csvFile = $this->request->getFile('file');
    $csvFileName = $csvFile->getName();
    
    // Retrieve country from POST data, or session if not set
    $country = $this->request->getPost('countries') ?? session()->get('user_account_system_id');

    // Split file name and extension
    $extAndName = explode('.', $csvFileName);

    // Sanitize the file name
    $sanitizeFileName = preg_replace('/[^A-Za-z0-9]/', '', $extAndName[0]);

    // Prepare file parts array
    $fileParts = [
        $sanitizeFileName,
        $extAndName[1] ?? '' // Default to empty string if no extension
    ];

    // Call the S3 upload method and output the result
    echo $awsAttachmentLibrary->s3MultiPartUpload($fileParts, $country);
}
}
