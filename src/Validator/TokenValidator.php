<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Validator;

use YousefKadah\ApplePayDecoder\Exceptions\InvalidTokenException;

/**
 * Token Structure Validator
 *
 * Validates the structure and format of Apple Pay payment tokens.
 */
class TokenValidator
{
    /**
     * Validate payment data structure
     *
     * @param array<string, mixed> $paymentData
     */
    public function validatePaymentData(array $paymentData): void
    {
        $required = ['version', 'data', 'signature', 'header'];
        foreach ($required as $field) {
            if (!isset($paymentData[$field])) {
                throw new InvalidTokenException("Missing required field: {$field}");
            }
        }

        $requiredHeader = ['publicKeyHash', 'ephemeralPublicKey', 'transactionId'];
        if (!isset($paymentData['header']) || !is_array($paymentData['header'])) {
            throw new InvalidTokenException("Missing or invalid header field");
        }

        foreach ($requiredHeader as $field) {
            if (!isset($paymentData['header'][$field])) {
                throw new InvalidTokenException("Missing required header field: {$field}");
            }
        }

        // Only support EC_v1 (RSA_v1 is deprecated)
        if (!isset($paymentData['version']) || !is_string($paymentData['version'])) {
            throw new InvalidTokenException("Missing or invalid version field");
        }

        if ($paymentData['version'] !== 'EC_v1') {
            throw new InvalidTokenException("Unsupported version: " . $paymentData['version']);
        }
    }

    /**
     * Validate token version specifically
     */
    public function validateTokenVersion(string $version): void
    {
        if ($version !== 'EC_v1') {
            throw new InvalidTokenException("Unsupported token version: {$version}");
        }
    }
}
