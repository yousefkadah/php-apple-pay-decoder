<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Crypto;

use YousefKadah\ApplePayDecoder\Config\MerchantConfig;
use YousefKadah\ApplePayDecoder\Exceptions\CryptographicException;
use YousefKadah\ApplePayDecoder\Exceptions\InvalidConfigurationException;
use Psr\Log\LoggerInterface;

/**
 * Private Key Manager
 *
 * Handles loading and management of merchant private keys.
 */
class PrivateKeyManager
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Load merchant private key from configuration
     */
    public function loadMerchantPrivateKey(MerchantConfig $config): \OpenSSLAsymmetricKey
    {
        $privateKeyContent = file_get_contents($config->privateKeyPath);
        if ($privateKeyContent === false) {
            throw new InvalidConfigurationException("Failed to read private key file: {$config->privateKeyPath}");
        }

        $privateKey = openssl_pkey_get_private($privateKeyContent);
        if (!$privateKey) {
            throw new CryptographicException("Failed to load private key: " . openssl_error_string());
        }

        $this->logger->debug('Merchant private key loaded successfully');
        return $privateKey;
    }
}
