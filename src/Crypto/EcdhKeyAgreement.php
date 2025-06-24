<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Crypto;

use YousefKadah\ApplePayDecoder\Exceptions\CryptographicException;
use YousefKadah\ApplePayDecoder\Exceptions\InvalidTokenException;
use Psr\Log\LoggerInterface;

/**
 * ECDH Key Agreement Handler
 *
 * Handles Elliptic Curve Diffie-Hellman key agreement operations
 * for Apple Pay token decryption.
 */
class EcdhKeyAgreement
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Perform ECDH key agreement between ephemeral and merchant keys
     */
    public function computeSharedSecret(string $rawEphemeralKey, \OpenSSLAsymmetricKey $merchantPrivateKey): string
    {
        // Extract X and Y coordinates
        $x = substr($rawEphemeralKey, 1, 32);
        $y = substr($rawEphemeralKey, 33, 32);

        $this->logger->debug('Performing ECDH', [
            'ephemeral_x_length' => strlen($x),
            'ephemeral_y_length' => strlen($y)
        ]);

        // Create ephemeral public key in PEM format
        $ephemeralKeyPem = $this->createEphemeralKeyPEM($x, $y);
        $ephemeralPublicKey = openssl_pkey_get_public($ephemeralKeyPem);

        if (!$ephemeralPublicKey) {
            throw new CryptographicException('Failed to create ephemeral public key: ' . openssl_error_string());
        }

        // Perform ECDH computation using OpenSSL
        $sharedSecret = openssl_pkey_derive($ephemeralPublicKey, $merchantPrivateKey, 32);

        if ($sharedSecret === false) {
            throw new CryptographicException('ECDH computation failed: ' . openssl_error_string());
        }

        $this->logger->debug('ECDH completed successfully', [
            'shared_secret_length' => strlen($sharedSecret)
        ]);

        // Convert to hex for KDF
        return bin2hex($sharedSecret);
    }

    /**
     * Extract raw public key from ephemeral public key (handles both raw and DER formats)
     */
    public function extractRawPublicKey(string $ephemeralPublicKey): string
    {
        // Check if it's already raw format (65 bytes, starts with 0x04)
        if (strlen($ephemeralPublicKey) === 65 && $ephemeralPublicKey[0] === "\x04") {
            $this->logger->debug('Ephemeral key is in raw format');
            return $ephemeralPublicKey;
        }

        // Try to parse as DER format
        if (strlen($ephemeralPublicKey) > 65) {
            $this->logger->debug('Attempting to parse DER-encoded ephemeral key');

            // Look for the uncompressed point indicator (0x04) followed by 64 bytes
            for ($i = 0; $i < strlen($ephemeralPublicKey) - 64; $i++) {
                if ($ephemeralPublicKey[$i] === "\x04") {
                    $remainingLength = strlen($ephemeralPublicKey) - $i;
                    if ($remainingLength >= 65) {
                        $rawKey = substr($ephemeralPublicKey, $i, 65);
                        $this->logger->debug('Successfully extracted raw key from DER format', [
                            'der_length' => strlen($ephemeralPublicKey),
                            'raw_key_offset' => $i,
                            'raw_key_length' => strlen($rawKey)
                        ]);
                        return $rawKey;
                    }
                }
            }
        }

        throw new InvalidTokenException('Unable to extract raw public key from ephemeral key data');
    }

    /**
     * Validate ephemeral public key format
     */
    public function validateEphemeralKey(string $rawKey): void
    {
        if (strlen($rawKey) !== 65) {
            throw new InvalidTokenException('Invalid ephemeral key length: ' . strlen($rawKey));
        }

        if ($rawKey[0] !== "\x04") {
            throw new InvalidTokenException('Invalid ephemeral key format: not uncompressed');
        }

        $this->logger->debug('Ephemeral key validation passed');
    }

    /**
     * Create ephemeral key in PEM format for OpenSSL
     */
    private function createEphemeralKeyPEM(string $x, string $y): string
    {
        // Create proper ASN.1 DER structure for P-256 public key
        $algorithmOid = pack('H*', '301306072a8648ce3d020106082a8648ce3d030107');
        $uncompressedPoint = "\x04" . $x . $y;
        $publicKeyBitString = pack('H*', '034200') . $uncompressedPoint;

        $publicKeyInfo = $algorithmOid . $publicKeyBitString;
        $totalLength = strlen($publicKeyInfo);

        if ($totalLength < 128) {
            $sequence = pack('H*', '30') . chr($totalLength) . $publicKeyInfo;
        } else {
            $sequence = pack('H*', '3081') . chr($totalLength) . $publicKeyInfo;
        }

        $pem = "-----BEGIN PUBLIC KEY-----\n";
        $pem .= chunk_split(base64_encode($sequence), 64, "\n");
        $pem .= "-----END PUBLIC KEY-----\n";

        return $pem;
    }
}
