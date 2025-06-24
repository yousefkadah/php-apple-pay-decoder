<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Validator;

use YousefKadah\ApplePayDecoder\Exceptions\InvalidConfigurationException;

/**
 * System Requirements Validator
 * 
 * Validates system requirements and configuration for Apple Pay decryption.
 */
class SystemValidator
{
    /**
     * Validate system requirements
     *
     * @return array<string>
     */
    public function validateSystemRequirements(): array
    {
        $issues = [];

        if (!extension_loaded('openssl')) {
            $issues[] = 'OpenSSL extension is required';
        }

        if (!extension_loaded('json')) {
            $issues[] = 'JSON extension is required';
        }

        return $issues;
    }

    /**
     * Validate system requirements and throw exception if invalid
     */
    public function validateSystemRequirementsStrict(): void
    {
        $issues = $this->validateSystemRequirements();
        
        if (!empty($issues)) {
            throw new InvalidConfigurationException('System requirements not met: ' . implode(', ', $issues));
        }
    }
}
