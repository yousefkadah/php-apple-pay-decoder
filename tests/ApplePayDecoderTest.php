<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Tests;

use PHPUnit\Framework\TestCase;
use YousefKadah\ApplePayDecoder\ApplePayDecoder;
use YousefKadah\ApplePayDecoder\Config\MerchantConfig;
use YousefKadah\ApplePayDecoder\Exceptions\InvalidConfigurationException;
use YousefKadah\ApplePayDecoder\Exceptions\InvalidTokenException;

class ApplePayDecoderTest extends TestCase
{
    private string $testCertPath;
    private string $testKeyPath;
    private MerchantConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary test files
        $this->testCertPath = tempnam(sys_get_temp_dir(), 'test_cert_') . '.pem';
        $this->testKeyPath = tempnam(sys_get_temp_dir(), 'test_key_') . '.key';

        // Mock certificate content
        file_put_contents($this->testCertPath, $this->getMockCertificate());
        file_put_contents($this->testKeyPath, $this->getMockPrivateKey());

        $this->config = new MerchantConfig(
            'merchant.test.example',
            $this->testCertPath,
            $this->testKeyPath
        );
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testCertPath)) {
            unlink($this->testCertPath);
        }
        if (file_exists($this->testKeyPath)) {
            unlink($this->testKeyPath);
        }
        parent::tearDown();
    }

    public function testConstructorValidatesSystemRequirements(): void
    {
        $this->expectNotToPerformAssertions();
        new ApplePayDecoder($this->config);
    }

    public function testValidateConfigurationReturnsIssuesForMissingFiles(): void
    {
        $config = new MerchantConfig(
            'merchant.test.example',
            '/non/existent/cert.pem',
            '/non/existent/key.pem'
        );

        $decoder = new ApplePayDecoder($config);
        $issues = $decoder->validateConfiguration();

        $this->assertNotEmpty($issues);
        $this->assertStringContainsString('Certificate file not found', implode(' ', $issues));
        $this->assertStringContainsString('Private key file not found', implode(' ', $issues));
    }

    public function testDecryptThrowsExceptionForInvalidTokenStructure(): void
    {
        $decoder = new ApplePayDecoder($this->config);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Missing required field: version');

        $decoder->decrypt([]);
    }

    public function testDecryptThrowsExceptionForUnsupportedVersion(): void
    {
        $decoder = new ApplePayDecoder($this->config);

        $token = [
            'version' => 'RSA_v1',
            'data' => 'test',
            'signature' => 'test',
            'header' => [
                'publicKeyHash' => 'test',
                'ephemeralPublicKey' => 'test',
                'transactionId' => 'test'
            ]
        ];

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Unsupported version: RSA_v1');

        $decoder->decrypt($token);
    }

    public function testDecryptThrowsExceptionForMissingHeaderFields(): void
    {
        $decoder = new ApplePayDecoder($this->config);

        $token = [
            'version' => 'EC_v1',
            'data' => 'test',
            'signature' => 'test',
            'header' => [
                'publicKeyHash' => 'test',
                // missing ephemeralPublicKey and transactionId
            ]
        ];

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Missing required header field: ephemeralPublicKey');

        $decoder->decrypt($token);
    }

    public function testConfigValidation(): void
    {
        $issues = $this->config->validate();
        $this->assertEmpty($issues);
    }

    public function testConfigValidationWithEmptyMerchantId(): void
    {
        $config = new MerchantConfig('', $this->testCertPath, $this->testKeyPath);
        $issues = $config->validate();

        $this->assertNotEmpty($issues);
        $this->assertContains('Merchant ID cannot be empty', $issues);
    }

    private function getMockCertificate(): string
    {
        return <<<PEM
-----BEGIN CERTIFICATE-----
MIIBkTCB+wIJAK1234567890ABCDEFMAoGCCqGSM49BAMCMBQxEjAQBgNVBAMMCVRl
c3QgQ2VydDAeFw0yMzAxMDEwMDAwMDBaFw0yNDAxMDEwMDAwMDBaMBQxEjAQBgNV
BAMMCVRlc3QgQ2VydDBZMBMGByqGSM49AgEGCCqGSM49AwEHA0IABFKjU4nDOOBX
/9Ci6YGRvLiUhQOGIYjqjGT0BOiQCzKhX4nC6YGRvLiUhQOGIYjqjGT0BOiQCzKh
X4nC6YGRvLiUhQOGIYjqjGT0BOiQCzKhX4nC6YGRvLiUhQOGIYjqjGT0BOiQCzKh
X4nC6YGRvLiUhQOGIYjqjGT0BOiQCzKhX4nC6YGRvLiUhQOGIYjqjGT0BOiQCzKh
X4nC6YGRvLiUhQOGIYjqjGT0BOiQCzKhX4nC6YGRvLiUhQOGIYjqjGT0BOiQCzKh
X4nC6YGRvLiUhQOGIYjqjGT0BOiQCzKhX4nC6YGRvLiUhQOGIYjqjGT0BOiQCzKh
-----END CERTIFICATE-----
PEM;
    }

    private function getMockPrivateKey(): string
    {
        return <<<PEM
-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIAbcdef1234567890abcdef1234567890abcdef1234567890abcdef12
34567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234
567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef123456
7890abcdef1234567890abcdef1234567890abcdef1234567890abcdef12345678
90abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890
abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890ab
-----END EC PRIVATE KEY-----
PEM;
    }
}
