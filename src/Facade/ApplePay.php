<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Facade;

use YousefKadah\ApplePayDecoder\ApplePayDecryptionService;
use YousefKadah\ApplePayDecoder\Config\MerchantConfig;
use YousefKadah\ApplePayDecoder\Exceptions\ApplePayDecryptionException;
use Psr\Log\LoggerInterface;

/**
 * Apple Pay Facade
 * 
 * Simplified interface for Apple Pay token decryption.
 * Provides static methods for easy usage without complex setup.
 */
class ApplePay
{
    private static ?ApplePayDecryptionService $defaultService = null;

    /**
     * Quick decrypt method with minimal configuration
     *
     * @param array<string, mixed> $paymentToken
     * @return array<string, mixed>
     * @throws ApplePayDecryptionException
     */
    public static function decrypt(
        array $paymentToken,
        string $merchantId,
        string $certificatePath,
        string $privateKeyPath,
        ?LoggerInterface $logger = null
    ): array {
        $config = new MerchantConfig($merchantId, $certificatePath, $privateKeyPath);
        $service = new ApplePayDecryptionService($config, $logger);
        
        return $service->decrypt($paymentToken);
    }

    /**
     * Create a configured service instance for reuse
     */
    public static function createService(
        string $merchantId,
        string $certificatePath,
        string $privateKeyPath,
        ?LoggerInterface $logger = null
    ): ApplePayDecryptionService {
        $config = new MerchantConfig($merchantId, $certificatePath, $privateKeyPath);
        return new ApplePayDecryptionService($config, $logger);
    }

    /**
     * Set default service for global usage
     */
    public static function setDefaultService(ApplePayDecryptionService $service): void
    {
        self::$defaultService = $service;
    }

    /**
     * Get default service instance
     */
    public static function getDefaultService(): ?ApplePayDecryptionService
    {
        return self::$defaultService;
    }

    /**
     * Quick decrypt using default service
     *
     * @param array<string, mixed> $paymentToken
     * @return array<string, mixed>
     * @throws ApplePayDecryptionException
     */
    public static function quickDecrypt(array $paymentToken): array
    {
        if (self::$defaultService === null) {
            throw new ApplePayDecryptionException(
                'No default service configured. Use setDefaultService() or decrypt() method instead.'
            );
        }

        return self::$defaultService->decrypt($paymentToken);
    }

    /**
     * Validate system requirements
     *
     * @return array<string>
     */
    public static function validateSystem(): array
    {
        $config = new MerchantConfig('test', '/dev/null', '/dev/null');
        $service = new ApplePayDecryptionService($config);
        
        return $service->validateConfiguration();
    }

    /**
     * Create service from environment variables
     * 
     * Expects: APPLE_PAY_MERCHANT_ID, APPLE_PAY_CERT_PATH, APPLE_PAY_KEY_PATH
     */
    public static function fromEnvironment(?LoggerInterface $logger = null): ApplePayDecryptionService
    {
        $merchantId = $_ENV['APPLE_PAY_MERCHANT_ID'] ?? null;
        $certPath = $_ENV['APPLE_PAY_CERT_PATH'] ?? null;
        $keyPath = $_ENV['APPLE_PAY_KEY_PATH'] ?? null;

        if (!$merchantId || !$certPath || !$keyPath) {
            throw new ApplePayDecryptionException(
                'Missing required environment variables: APPLE_PAY_MERCHANT_ID, APPLE_PAY_CERT_PATH, APPLE_PAY_KEY_PATH'
            );
        }

        return self::createService($merchantId, $certPath, $keyPath, $logger);
    }
}
