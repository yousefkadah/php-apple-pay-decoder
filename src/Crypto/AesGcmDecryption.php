<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Crypto;

use YousefKadah\ApplePayDecoder\Exceptions\CryptographicException;
use Psr\Log\LoggerInterface;

/**
 * AES-GCM Encryption/Decryption Handler
 *
 * Handles AES-GCM decryption operations for Apple Pay payment data.
 */
class AesGcmDecryption
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Perform AES-GCM decryption
     *
     * @param array{encryption_key: string, kdf_info: string} $derivedKeys
     */
    public function decrypt(string $encryptedData, array $derivedKeys): string
    {
        // Apple Pay AES-GCM: IV is 16 zero bytes, tag is last 16 bytes
        $iv = str_repeat("\x00", 16);
        $tag = substr($encryptedData, -16);
        $ciphertext = substr($encryptedData, 0, -16);

        $this->logger->debug('AES-GCM decryption attempt', [
            'iv_length' => strlen($iv),
            'ciphertext_length' => strlen($ciphertext),
            'tag_length' => strlen($tag)
        ]);

        $decryptedData = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $derivedKeys['encryption_key'],
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decryptedData === false) {
            $error = openssl_error_string();
            throw new CryptographicException('AES-GCM decryption failed: ' . $error);
        }

        $this->logger->debug('AES-GCM decryption successful', [
            'decrypted_length' => strlen($decryptedData)
        ]);

        return $decryptedData;
    }
}
