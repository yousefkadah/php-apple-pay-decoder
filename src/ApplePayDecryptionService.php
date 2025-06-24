<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder;

use YousefKadah\ApplePayDecoder\Config\MerchantConfig;
use YousefKadah\ApplePayDecoder\Crypto\EcdhKeyAgreement;
use YousefKadah\ApplePayDecoder\Crypto\KeyDerivationFunction;
use YousefKadah\ApplePayDecoder\Crypto\AesGcmDecryption;
use YousefKadah\ApplePayDecoder\Crypto\PrivateKeyManager;
use YousefKadah\ApplePayDecoder\Parser\TokenDataParser;
use YousefKadah\ApplePayDecoder\Validator\TokenValidator;
use YousefKadah\ApplePayDecoder\Validator\SystemValidator;
use YousefKadah\ApplePayDecoder\Exceptions\ApplePayDecryptionException;
use YousefKadah\ApplePayDecoder\Exceptions\InvalidConfigurationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Apple Pay Decryption Service
 *
 * Main service class that orchestrates the decryption process
 * using specialized components for each step.
 */
class ApplePayDecryptionService
{
    private LoggerInterface $logger;
    private EcdhKeyAgreement $ecdhKeyAgreement;
    private KeyDerivationFunction $kdf;
    private AesGcmDecryption $aesGcm;
    private PrivateKeyManager $keyManager;
    private TokenDataParser $parser;
    private TokenValidator $tokenValidator;
    private SystemValidator $systemValidator;
    private MerchantConfig $config;

    public function __construct(
        MerchantConfig $config,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();

        // Initialize all components
        $this->ecdhKeyAgreement = new EcdhKeyAgreement($this->logger);
        $this->kdf = new KeyDerivationFunction($this->logger, $this->config->merchantId);
        $this->aesGcm = new AesGcmDecryption($this->logger);
        $this->keyManager = new PrivateKeyManager($this->logger);
        $this->parser = new TokenDataParser($this->logger);
        $this->tokenValidator = new TokenValidator();
        $this->systemValidator = new SystemValidator();

        // Validate system requirements
        $this->systemValidator->validateSystemRequirementsStrict();
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
        $this->tokenValidator->validatePaymentData($paymentData);

        $this->logger->info('Apple Pay decryption started', [
            'version' => $paymentData['version'],
            'transaction_id' => $paymentData['header']['transactionId'] ?? 'unknown'
        ]);

        // Validate token version
        $this->tokenValidator->validateTokenVersion($paymentData['version']);

        return $this->performECDecryption($paymentData);
    }

    /**
     * Perform EC_v1 token decryption using specialized components
     *
     * @param array<string, mixed> $paymentData
     * @return array<string, mixed>
     */
    private function performECDecryption(array $paymentData): array
    {
        try {
            // Step 1: Extract token components
            $components = $this->parser->extractTokenComponents($paymentData);

            // Step 2: Extract and validate ephemeral public key
            $this->logger->debug('Extracting ephemeral public key');
            $rawEphemeralKey = $this->ecdhKeyAgreement->extractRawPublicKey($components['ephemeralPublicKey']);
            $this->ecdhKeyAgreement->validateEphemeralKey($rawEphemeralKey);

            // Step 3: Load merchant private key
            $this->logger->debug('Loading merchant private key');
            $merchantPrivateKey = $this->keyManager->loadMerchantPrivateKey($this->config);

            // Step 4: Perform ECDH
            $this->logger->debug('Performing ECDH operation');
            $sharedSecret = $this->ecdhKeyAgreement->computeSharedSecret($rawEphemeralKey, $merchantPrivateKey);

            // Step 5: Perform KDF
            $this->logger->debug('Performing KDF');
            $derivedKeys = $this->kdf->deriveKeys($sharedSecret);

            // Step 6: AES-GCM decryption
            $this->logger->debug('Performing AES-GCM decryption');
            $decryptedData = $this->aesGcm->decrypt($components['encryptedData'], $derivedKeys);

            // Step 7: Parse decrypted JSON
            $this->logger->debug('Parsing decrypted payment data');
            $paymentInfo = $this->parser->parseDecryptedPaymentData($decryptedData);

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
     * Check if certificates are properly configured
     *
     * @return array<string>
     */
    public function validateConfiguration(): array
    {
        $issues = $this->config->validate();
        $systemIssues = $this->systemValidator->validateSystemRequirements();

        return array_merge($issues, $systemIssues);
    }
}
