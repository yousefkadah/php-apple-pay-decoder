<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Parser;

use YousefKadah\ApplePayDecoder\Exceptions\InvalidTokenException;
use Psr\Log\LoggerInterface;

/**
 * Token Data Parser
 *
 * Handles parsing and extraction of data from Apple Pay tokens.
 */
class TokenDataParser
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Extract and decode token components
     *
     * @param array<string, mixed> $paymentData
     * @return array{ephemeralPublicKey: string, encryptedData: string, transactionId: string}
     */
    public function extractTokenComponents(array $paymentData): array
    {
        $this->logger->debug('Extracting token components');

        // Validate required fields exist and are strings
        if (!isset($paymentData['header']) || !is_array($paymentData['header'])) {
            throw new InvalidTokenException('Missing or invalid header field');
        }
        if (!isset($paymentData['header']['ephemeralPublicKey']) || !is_string($paymentData['header']['ephemeralPublicKey'])) {
            throw new InvalidTokenException('Missing or invalid ephemeralPublicKey in header');
        }
        if (!isset($paymentData['data']) || !is_string($paymentData['data'])) {
            throw new InvalidTokenException('Missing or invalid data field');
        }
        if (!isset($paymentData['header']['transactionId']) || !is_string($paymentData['header']['transactionId'])) {
            throw new InvalidTokenException('Missing or invalid transactionId in header');
        }

        // Now we know the types are correct
        $header = $paymentData['header'];
        
        return [
            'ephemeralPublicKey' => base64_decode($header['ephemeralPublicKey']),
            'encryptedData' => base64_decode($paymentData['data']),
            'transactionId' => $this->decodeTransactionId($header['transactionId'])
        ];
    }

    /**
     * Parse decrypted payment data
     *
     * @return array<string, mixed>
     */
    public function parseDecryptedPaymentData(string $decryptedData): array
    {
        $paymentInfo = json_decode($decryptedData, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($paymentInfo)) {
            throw new InvalidTokenException('Failed to parse JSON: ' . json_last_error_msg());
        }

        $this->logger->debug('Payment data parsed successfully', [
            'fields_count' => count($paymentInfo)
        ]);

        return $paymentInfo;
    }

    /**
     * Decode transaction ID from hex string with proper padding
     */
    private function decodeTransactionId(string $transactionId): string
    {
        // Ensure even length for hex2bin
        if (strlen($transactionId) % 2 !== 0) {
            $transactionId = '0' . $transactionId;
        }

        $decoded = hex2bin($transactionId);
        if ($decoded === false) {
            throw new InvalidTokenException('Invalid transaction ID format');
        }

        return $decoded;
    }
}
