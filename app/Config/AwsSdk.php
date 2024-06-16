<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use Aws\Sdk;
use Aws\AwsClientInterface;

/**
 * AWS SDK Configuration Class
 *
 * This class is responsible for initializing the AWS SDK and providing a method
 * to create clients for different AWS services.
 */
class AwsSdk extends BaseConfig
{
    /**
     * @var Sdk The AWS SDK instance
     */
    public $sdk;

    /**
     * Constructor
     *
     * Initializes the AWS SDK with the provided configuration.
     */
    public function __construct()
    {
        parent::__construct();

        $this->sdk = new Sdk([
            'region'  => $_ENV['AWS_DEFAULT_REGION'], 
            'version' => 'latest',
            'profile'=> $_ENV['AWS_CREDENTIALS_PROFILE'],
        ]);
    }

    /**
     * Get AWS SDK Client
     *
     * Creates and returns a client for the specified AWS service.
     *
     * @param string $service The name of the AWS service (e.g., 'S3', 'DynamoDB', 'SNS', 'SSM' etc)
     * @return \Aws\AwsClientInterface The AWS SDK client for the specified service
     */
    public function getClient($service):AwsClientInterface
    {
        return $this->sdk->createClient($service);
    }
}