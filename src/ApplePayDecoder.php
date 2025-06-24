<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder;

use YousefKadah\ApplePayDecoder\Config\MerchantConfig;
use YousefKadah\ApplePayDecoder\Exceptions\ApplePayDecryptionException;
use Psr\Log\LoggerInterface;

/**
 * Apple Pay Token Decoder (Legacy Compatibility)
 *
 * @deprecated Use ApplePayDecryptionService directly for new projects
 * This class is maintained for backward compatibility only.
 */
class ApplePayDecoder
{
    private ApplePayDecryptionService $service;

    public function __construct(
        MerchantConfig $config,
        ?LoggerInterface $logger = null
    ) {
        $this->service = new ApplePayDecryptionService($config, $logger);
    }

    /**
     * Decrypt Apple Pay payment token
     *
     * @param array<string, mixed> $paymentData
     * @return array<string, mixed>
     * @throws ApplePayDecryptionException
     */
    public function decrypt(array $paymentData): array
    {
        return $this->service->decrypt($paymentData);
    }

    /**
     * Check if certificates are properly configured
     *
     * @return array<string>
     */
    public function validateConfiguration(): array
    {
        return $this->service->validateConfiguration();
    }
}
