<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Crypto;

use YousefKadah\ApplePayDecoder\Exceptions\CryptographicException;
use Psr\Log\LoggerInterface;

/**
 * Key Derivation Function Implementation
 *
 * Implements the Concat KDF (NIST SP 800-56A) as required by Apple Pay
 * for deriving encryption keys from shared secrets.
 */
class KeyDerivationFunction
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $merchantId
    ) {
    }

    /**
     * Perform Key Derivation Function (Apple Pay specific)
     *
     * @return array{encryption_key: string, kdf_info: string}
     */
    public function deriveKeys(string $sharedSecret): array
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

        $binarySecret = hex2bin($sharedSecret); // Shared secret as binary
        if ($binarySecret === false) {
            throw new CryptographicException('Invalid hex string in shared secret');
        }
        hash_update($hash, $binarySecret);
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
        $merchantIdHash = hash('sha256', $this->merchantId, true);

        return $kdfAlgorithm . $kdfPartyU . $merchantIdHash;
    }
}
