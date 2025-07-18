<?php

namespace App\Libraries\Session\Handlers;

use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Result;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use SessionHandlerInterface;
use Config\Session as SessionConfig; // Alias for clarity

/**
 * DynamoDbSessionHandler
 *
 * A custom session handler for CodeIgniter 4 that uses AWS DynamoDB
 * for session storage.
 *
 * Requires the AWS SDK for PHP (aws/aws-sdk-php).
 * Install with: composer require aws/aws-sdk-php
 */
class DynamoDbSessionHandler extends BaseHandler
{
    /**
     * The DynamoDB client instance.
     *
     * @var DynamoDbClient
     */
    protected $dynamoDbClient;

    /**
     * The DynamoDB table name for sessions.
     *
     * @var string
     */
    protected $tableName;

    /**
     * The primary key column name in the DynamoDB table.
     *
     * @var string
     */
    protected $primaryKey = 'id'; // Default primary key name

    /**
     * The column name for session data.
     *
     * @var string
     */
    protected $dataColumn = 'data'; // Default data column name

    /**
     * The column name for last activity timestamp.
     *
     * @var string
     */
    protected $timestampColumn = 'timestamp'; // Default timestamp column name

    /**
     * The column name for IP address (optional).
     *
     * @var string
     */
    protected $ipAddressColumn = 'ip_address'; // Default IP address column name

    /**
     * The column name for user agent (optional).
     *
     * @var string
     */
    protected $userAgentColumn = 'user_agent'; // Default user agent column name

    protected $request;

    protected $sessionData;

    protected $sessionSavePath;

    protected $sessionExpiration = 300000;

    /**
     * Constructor.
     *
     * @param SessionConfig $config    The session configuration object.
     * @param string        $ipAddress The client's IP address.
     */
    public function __construct(SessionConfig $config, string $ipAddress)
    {
        // Pass the config and IP address to the parent constructor
        parent::__construct($config, $ipAddress);

        // Get the specific DynamoDB handler options from the session config
        $options = (array) $config; // $config->sessionHandlers[self::class] ?? [];

        // log_message('error', json_encode($options));

        // Validate required options for DynamoDB
        if (empty($options['savePath'])) {
            throw new \RuntimeException('DynamoDB session handler requires a "savePath" option in Config\\Session::$sessionHandlers.');
        }
        if (empty($options['region'])) {
            throw new \RuntimeException('DynamoDB session handler requires "region" and "version" options for AWS SDK in Config\\Session::$sessionHandlers.');
        }

        $this->tableName = $options['savePath'];
        $this->primaryKey = $options['primaryKey'] ?? $this->primaryKey;
        $this->dataColumn = $options['dataColumn'] ?? $this->dataColumn;
        $this->timestampColumn = $options['timestampColumn'] ?? $this->timestampColumn;
        $this->ipAddressColumn = $options['ipAddressColumn'] ?? $this->ipAddressColumn;
        $this->userAgentColumn = $options['userAgentColumn'] ?? $this->userAgentColumn;

        // Initialize DynamoDB client
        $this->dynamoDbClient = new DynamoDbClient([
            'region'  => $options['region'],
            // 'version' => $options['version'],
            // 'credentials' => [ // Add credentials if not using IAM roles
            //     'key'    => $options['key'] ?? null,
            //     'secret' => $options['secret'] ?? null,
            // ],
            // You can add more AWS SDK options here, e.g., 'endpoint' for local DynamoDB
            // 'endpoint' => 'http://localhost:8000',
        ]);

        // Set the session save path to the table name
        // This is a convention for BaseHandler, though not directly used by DynamoDB
        $this->sessionSavePath = $this->tableName;

        $this->request = \Config\Services::request();
    }

    /**
     * Open the session.
     *
     * @param string $savePath The path where to store/retrieve the session.
     * @param string $name     The session name.
     *
     * @return bool
     */
    public function open($savePath, $name): bool
    {
        // In DynamoDB, 'open' typically doesn't do much beyond ensuring
        // the client is initialized, which is done in the constructor.
        // We can optionally check if the table exists here, but it's often
        // better to assume it exists or handle creation outside the handler.
        return true;
    }

    /**
     * Close the session.
     *
     * @return bool
     */
    public function close(): bool
    {
        // No specific action needed to "close" a connection for DynamoDB.
        // The client manages its own connections.
        return true;
    }

    /**
     * Read the session data.
     *
     * @param string $sessionId The session ID.
     *
     * @return string The session data or an empty string if not found.
     */
    public function read($sessionId): string
    {
        $this->sessionID = $sessionId;

        try {
            $result = $this->dynamoDbClient->getItem([
                'TableName' => $this->tableName,
                'Key' => [
                    $this->primaryKey => ['S' => $sessionId],
                ],
                'ConsistentRead' => true, // Ensure we get the latest data
            ]);

            if (isset($result['Item']) && isset($result['Item'][$this->dataColumn]['S'])) {
                $item = $result['Item'];

                // Check for expiration
                // Note: $this->sessionExpiration is inherited from BaseHandler and comes from $config->expiration
                $timestamp = (int) ($item[$this->timestampColumn]['N'] ?? 0);
                if ($timestamp + $this->sessionExpiration < time()) {
                    // Session has expired, destroy it and return empty string
                    $this->destroy($sessionId);
                    return '';
                }

                $this->sessionData = $item[$this->dataColumn]['S'];
                return $this->sessionData;
            }
        } catch (DynamoDbException $e) {
            log_message('error', 'DynamoDB Session Read Error: {message}', ['message' => $e->getMessage()]);
        }

        $this->sessionData = '';
        return '';
    }

    /**
     * Write the session data.
     *
     * @param string $sessionId   The session ID.
     * @param string $sessionData The session data.
     *
     * @return bool
     */
    public function write($sessionId, $sessionData): bool
    {
        $this->sessionID = $sessionId;
        $this->sessionData = $sessionData;

        // Use $this->request which is available from BaseHandler
        $ipAddress = $this->request->getIPAddress();
        $userAgent = substr($this->request->getUserAgent()->getAgentString(), 0, 120); // Limit user agent length

        try {
            $item = [
                $this->primaryKey => ['S' => $sessionId],
                $this->dataColumn => ['S' => $sessionData],
                $this->timestampColumn => ['N' => (string) time()],
                $this->ipAddressColumn => ['S' => $ipAddress],
                $this->userAgentColumn => ['S' => $userAgent],
            ];

            $this->dynamoDbClient->putItem([
                'TableName' => $this->tableName,
                'Item' => $item,
            ]);

            return true;
        } catch (DynamoDbException $e) {
            log_message('error', 'DynamoDB Session Write Error: {message}', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Destroy the session.
     *
     * @param string $sessionId The session ID.
     *
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        try {
            $this->dynamoDbClient->deleteItem([
                'TableName' => $this->tableName,
                'Key' => [
                    $this->primaryKey => ['S' => $sessionId],
                ],
            ]);

            return true;
        } catch (DynamoDbException $e) {
            log_message('error', 'DynamoDB Session Destroy Error: {message}', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Garbage collection.
     *
     * In DynamoDB, TTL (Time To Live) is the preferred way to handle garbage collection.
     * This method can be left empty if TTL is configured on the DynamoDB table.
     * If TTL is not used, you would need to scan and delete expired items,
     * which can be expensive and is generally not recommended for large tables.
     *
     * @param int $maxlifetime The maximum lifetime of a session.
     *
     * @return int The number of deleted sessions.
     */
    public function gc($maxlifetime): int
    {
        // It is highly recommended to use DynamoDB's TTL feature for session expiration.
        // If TTL is configured on your table with 'timestamp' as the TTL attribute,
        // DynamoDB will automatically delete expired items.
        // If you cannot use TTL, you would implement a scan and delete logic here,
        // but be aware of the performance implications for large tables.
        log_message('info', 'DynamoDB Session GC called. Recommend using DynamoDB TTL for efficient garbage collection.');

        return 0; // Return 0 as we rely on TTL for actual cleanup
    }
}