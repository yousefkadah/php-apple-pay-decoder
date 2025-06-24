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
 *
 * Usage Examples:
 *
 * 1. Simple one-time decryption:
 *    $result = ApplePay::decrypt($token, $merchantId, $certPath, $keyPath);
 *
 * 2. Using environment variables:
 *    ApplePay::configureFromEnvironment();
 *    $result = ApplePay::quickDecrypt($token);
 *
 * 3. With custom configuration:
 *    ApplePay::configure($merchantId, $certPath, $keyPath, $logger);
 *    $result = ApplePay::quickDecrypt($token);
 *
 * 4. Check system requirements:
 *    $issues = ApplePay::validateSystem();
 *    if (empty($issues)) { // all good }
 */
class ApplePay
{
    private static ?ApplePayDecryptionService $defaultService = null;
    private static ?MerchantConfig $defaultConfig = null;

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
     * Configure default service for repeated use
     */
    public static function configure(
        string $merchantId,
        string $certificatePath,
        string $privateKeyPath,
        ?LoggerInterface $logger = null
    ): void {
        self::$defaultConfig = new MerchantConfig($merchantId, $certificatePath, $privateKeyPath);
        self::$defaultService = new ApplePayDecryptionService(self::$defaultConfig, $logger);
    }

    /**
     * Configure from environment variables
     *
     * Expects: APPLE_PAY_MERCHANT_ID, APPLE_PAY_CERT_PATH, APPLE_PAY_KEY_PATH
     */
    public static function configureFromEnvironment(?LoggerInterface $logger = null): void
    {
        $merchantId = $_ENV['APPLE_PAY_MERCHANT_ID'] ?? null;
        $certPath = $_ENV['APPLE_PAY_CERT_PATH'] ?? null;
        $keyPath = $_ENV['APPLE_PAY_KEY_PATH'] ?? null;

        if (!$merchantId || !$certPath || !$keyPath) {
            throw new ApplePayDecryptionException(
                'Missing required environment variables: APPLE_PAY_MERCHANT_ID, APPLE_PAY_CERT_PATH, APPLE_PAY_KEY_PATH'
            );
        }

        self::configure($merchantId, $certPath, $keyPath, $logger);
    }

    /**
     * Check if facade is configured
     */
    public static function isConfigured(): bool
    {
        return self::$defaultService !== null;
    }

    /**
     * Get current configuration (if any)
     */
    public static function getConfig(): ?MerchantConfig
    {
        return self::$defaultConfig;
    }

    /**
     * Reset configuration
     */
    public static function reset(): void
    {
        self::$defaultService = null;
        self::$defaultConfig = null;
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
                'No default service configured. ' .
                'Use configure(), configureFromEnvironment(), or decrypt() method instead.'
            );
        }

        return self::$defaultService->decrypt($paymentToken);
    }

    /**
     * Validate configuration and system requirements
     *
     * @return array<string> Array of validation issues (empty if all OK)
     */
    public static function validateConfiguration(): array
    {
        if (self::$defaultService === null) {
            return ['No service configured'];
        }

        return self::$defaultService->validateConfiguration();
    }

    /**
     * Validate system requirements only (without configuration)
     *
     * @return array<string>
     */
    public static function validateSystem(): array
    {
        $config = new MerchantConfig('test', '/dev/null', '/dev/null');
        $service = new ApplePayDecryptionService($config);

        return $service->validateConfiguration();
    }

    // Legacy methods for backward compatibility

    /**
     * Create a configured service instance for reuse
     *
     * @deprecated Use configure() for setting default service or new ApplePayDecryptionService() directly
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
     *
     * @deprecated Use configure() instead
     */
    public static function setDefaultService(ApplePayDecryptionService $service): void
    {
        self::$defaultService = $service;
    }

    /**
     * Get default service instance
     *
     * @deprecated Access service through facade methods instead
     */
    public static function getDefaultService(): ?ApplePayDecryptionService
    {
        return self::$defaultService;
    }

    /**
     * Create service from environment variables (backward compatibility)
     *
     * @deprecated Use configureFromEnvironment() instead
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
