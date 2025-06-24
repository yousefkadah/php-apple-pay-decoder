<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder;

use YousefKadah\ApplePayDecoder\Config\MerchantConfig;
use YousefKadah\ApplePayDecoder\Exceptions\ApplePayDecryptionException;
use YousefKadah\ApplePayDecoder\Exceptions\InvalidConfigurationException;
use YousefKadah\ApplePayDecoder\Exceptions\InvalidTokenException;
use YousefKadah\ApplePayDecoder\Exceptions\CryptographicException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Apple Pay Token Decoder
 *
 * Production-ready Apple Pay payment token decryption using real cryptographic operations.
 * Performs actual ECDH, KDF, and AES-GCM decryption for EC_v1 tokens.
 */
class ApplePayDecoder
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly MerchantConfig $config,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->validateSystemRequirements();
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
        // Validate configuration
        $configIssues = $this->config->validate();
        if (!empty($configIssues)) {
            throw new InvalidConfigurationException('Configuration issues: ' . implode(', ', $configIssues));
        }

        // Validate payment data structure
        $this->validatePaymentData($paymentData);

        $this->logger->info('Apple Pay decryption started', [
            'version' => $paymentData['version'],
            'transaction_id' => $paymentData['header']['transactionId'] ?? 'unknown'
        ]);

        // Only support EC_v1 tokens (RSA_v1 is deprecated)
        if ($paymentData['version'] !== 'EC_v1') {
            throw new InvalidTokenException("Unsupported token version: {$paymentData['version']}");
        }

        return $this->performECDecryption($paymentData);
    }

    /**
     * Validate system requirements
     */
    private function validateSystemRequirements(): void
    {
        if (!extension_loaded('openssl')) {
            throw new InvalidConfigurationException('OpenSSL extension is required');
        }

        if (!extension_loaded('json')) {
            throw new InvalidConfigurationException('JSON extension is required');
        }
    }

    /**
     * Perform EC_v1 token decryption using real cryptographic operations
     *
     * @param array<string, mixed> $paymentData
     * @return array<string, mixed>
     */
    private function performECDecryption(array $paymentData): array
    {
        try {
            // Step 1: Extract components
            $this->logger->debug('Extracting token components');
            $ephemeralPublicKey = base64_decode($paymentData['header']['ephemeralPublicKey']);
            $encryptedData = base64_decode($paymentData['data']);
            $transactionId = $this->decodeTransactionId($paymentData['header']['transactionId']);

            // Step 2: Extract and validate ephemeral public key
            $this->logger->debug('Extracting ephemeral public key');
            $rawEphemeralKey = $this->extractRawPublicKey($ephemeralPublicKey);
            $this->validateEphemeralKey($rawEphemeralKey);

            // Step 3: Load merchant private key
            $this->logger->debug('Loading merchant private key');
            $merchantPrivateKey = $this->loadMerchantPrivateKey();

            // Step 4: Perform ECDH
            $this->logger->debug('Performing ECDH operation');
            $sharedSecret = $this->performECDH($rawEphemeralKey, $merchantPrivateKey);

            // Step 5: Perform KDF
            $this->logger->debug('Performing KDF');
            $derivedKeys = $this->performKDF($sharedSecret);

            // Step 6: AES-GCM decryption
            $this->logger->debug('Performing AES-GCM decryption');
            $decryptedData = $this->performAESGCMDecryption($encryptedData, $derivedKeys);

            // Step 7: Parse decrypted JSON
            $this->logger->debug('Parsing decrypted payment data');
            $paymentInfo = $this->parseDecryptedPaymentData($decryptedData);

            $this->logger->info('Decryption completed successfully');

            return $paymentInfo;
        } catch (ApplePayDecryptionException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during decryption', ['error' => $e->getMessage()]);
            throw new ApplePayDecryptionException('Decryption failed: ' . $e->getMessage(), 0, $e);
        }
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

    /**
     * Extract raw public key from ephemeral public key (handles both raw and DER formats)
     */
    private function extractRawPublicKey(string $ephemeralPublicKey): string
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
    private function validateEphemeralKey(string $rawKey): void
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
     * Load merchant private key
     *
     * @return \OpenSSLAsymmetricKey
     */
    private function loadMerchantPrivateKey(): \OpenSSLAsymmetricKey
    {
        $privateKeyContent = file_get_contents($this->config->privateKeyPath);
        if ($privateKeyContent === false) {
            throw new InvalidConfigurationException("Failed to read private key file: {$this->config->privateKeyPath}");
        }

        $privateKey = openssl_pkey_get_private($privateKeyContent);
        if (!$privateKey) {
            throw new CryptographicException("Failed to load private key: " . openssl_error_string());
        }

        $this->logger->debug('Merchant private key loaded successfully');
        return $privateKey;
    }

    /**
     * Perform ECDH key agreement using OpenSSL
     */
    private function performECDH(string $rawEphemeralKey, \OpenSSLAsymmetricKey $merchantPrivateKey): string
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

    /**
     * Perform Key Derivation Function (Apple Pay specific)
     *
     * @return array{encryption_key: string, kdf_info: string}
     */
    private function performKDF(string $sharedSecret): array
    {
        $kdfInfo = $this->buildKDFInfo();
        $derivedKey = $this->concatKDF($sharedSecret, 32, $kdfInfo);

        $this->logger->debug('KDF performed successfully', [
            'input_length' => strlen($sharedSecret),
            'output_length' => strlen($derivedKey)
        ]);

        return [
            'encryption_key' => $derivedKey,
            'kdf_info' => $kdfInfo
        ];
    }

    /**
     * Concat KDF implementation (NIST SP 800-56A)
     */
    private function concatKDF(string $sharedSecret, int $keyLength, string $otherInfo): string
    {
        $hash = hash_init('sha256');
        hash_update($hash, pack('H*', '00000001')); // Counter: 00 00 00 01
        hash_update($hash, hex2bin($sharedSecret)); // Shared secret as binary
        hash_update($hash, $otherInfo);

        $derivedKey = hash_final($hash, true);
        return substr($derivedKey, 0, $keyLength);
    }

    /**
     * Build KDF info for Apple Pay
     */
    private function buildKDFInfo(): string
    {
        $kdfAlgorithm = "\x0d" . 'id-aes256-GCM';
        $kdfPartyU = 'Apple';
        $merchantIdHash = hash('sha256', $this->config->merchantId, true);

        return $kdfAlgorithm . $kdfPartyU . $merchantIdHash;
    }

    /**
     * Perform AES-GCM decryption
     *
     * @param array{encryption_key: string, kdf_info: string} $derivedKeys
     */
    private function performAESGCMDecryption(string $encryptedData, array $derivedKeys): string
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

    /**
     * Parse decrypted payment data
     *
     * @return array<string, mixed>
     */
    private function parseDecryptedPaymentData(string $decryptedData): array
    {
        $paymentInfo = json_decode($decryptedData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidTokenException('Failed to parse JSON: ' . json_last_error_msg());
        }

        $this->logger->debug('Payment data parsed successfully', [
            'fields_count' => count($paymentInfo)
        ]);

        return $paymentInfo;
    }

    /**
     * Validate payment data structure
     *
     * @param array<string, mixed> $paymentData
     */
    private function validatePaymentData(array $paymentData): void
    {
        $required = ['version', 'data', 'signature', 'header'];
        foreach ($required as $field) {
            if (!isset($paymentData[$field])) {
                throw new InvalidTokenException("Missing required field: {$field}");
            }
        }

        $requiredHeader = ['publicKeyHash', 'ephemeralPublicKey', 'transactionId'];
        foreach ($requiredHeader as $field) {
            if (!isset($paymentData['header'][$field])) {
                throw new InvalidTokenException("Missing required header field: {$field}");
            }
        }

        // Only support EC_v1 (RSA_v1 is deprecated)
        if ($paymentData['version'] !== 'EC_v1') {
            throw new InvalidTokenException("Unsupported version: {$paymentData['version']}");
        }
    }

    /**
     * Check if certificates are properly configured
     *
     * @return array<string>
     */
    public function validateConfiguration(): array
    {
        $issues = $this->config->validate();

        if (!extension_loaded('openssl')) {
            $issues[] = "OpenSSL extension not loaded";
        }

        if (!extension_loaded('json')) {
            $issues[] = "JSON extension not loaded";
        }

        return $issues;
    }
}
