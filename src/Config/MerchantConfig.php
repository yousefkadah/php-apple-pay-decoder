<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Config;

/**
 * Merchant Configuration for Apple Pay Decryption
 */
class MerchantConfig
{
    /**
     * @var string
     */
    public $merchantId;

    /**
     * @var string
     */
    public $certificatePath;

    /**
     * @var string
     */
    public $privateKeyPath;

    public function __construct(
        string $merchantId,
        string $certificatePath,
        string $privateKeyPath
    ) {
        $this->merchantId = $merchantId;
        $this->certificatePath = $certificatePath;
        $this->privateKeyPath = $privateKeyPath;
    }

    /**
     * Validate that all required files exist and are readable
     *
     * @return array<string>
     */
    public function validate(): array
    {
        $issues = [];

        if (empty($this->merchantId)) {
            $issues[] = 'Merchant ID cannot be empty';
        }

        if (!file_exists($this->certificatePath)) {
            $issues[] = "Certificate file not found: {$this->certificatePath}";
        } elseif (!is_readable($this->certificatePath)) {
            $issues[] = "Certificate file is not readable: {$this->certificatePath}";
        }

        if (!file_exists($this->privateKeyPath)) {
            $issues[] = "Private key file not found: {$this->privateKeyPath}";
        } elseif (!is_readable($this->privateKeyPath)) {
            $issues[] = "Private key file is not readable: {$this->privateKeyPath}";
        }

        return $issues;
    }
}
