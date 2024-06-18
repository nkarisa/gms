<?php 

namespace App\Libraries\System;

use Config\AwsSdk;

/**
 * Class AwsParameterStoreLibrary
 *
 * This class is responsible for interacting with AWS Parameter Store.
 * It retrieves parameter values from the store and falls back to environment variables if not found.
 */
class AwsParameterStoreLibrary {

    /**
     * Retrieves a parameter value from AWS Parameter Store.
     *
     * @param string $parameter_name The name of the parameter to retrieve.
     * @return string The value of the parameter.
     * @throws \Exception If an error occurs while retrieving the parameter.
     */
    public function getParameterValue($parameter_name):string
    {
        // Instantiate the AWS SDK
        $awsSdk = new AwsSdk();
        // Get the SSM client from the AWS SDK
        $ssmClient = $awsSdk->getClient('ssm');
        // Initialize the parameter value
        $parameterValue = '';

        try {
            // Attempt to retrieve the parameter value from AWS Parameter Store
            $result = $ssmClient->getParameter([
                'Name' => $parameter_name, 
                'WithDecryption' => true
            ]);

            // Extract the parameter value from the result
            $parameterValue = $result['Parameter']['Value'];
        } catch (\Exception $e) {
            // If an error occurs, fall back to retrieving the parameter value from environment variables
            log_message('error', 'Exception occurred: ' . $e->getMessage());
            $parameterValue = $_ENV[strtoupper($parameter_name)];
        }

        // Return the parameter value
        return $parameterValue;
    }
}