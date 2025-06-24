<?php

declare(strict_types=1);

namespace YousefKadah\ApplePayDecoder\Tests;

use PHPUnit\Framework\TestCase;
use YousefKadah\ApplePayDecoder\Config\MerchantConfig;

class MerchantConfigTest extends TestCase
{
    public function testValidConfigurationPasses(): void
    {
        $certPath = tempnam(sys_get_temp_dir(), 'test_cert_');
        $keyPath = tempnam(sys_get_temp_dir(), 'test_key_');

        file_put_contents($certPath, 'test cert content');
        file_put_contents($keyPath, 'test key content');

        $config = new MerchantConfig('merchant.test', $certPath, $keyPath);
        $issues = $config->validate();

        $this->assertEmpty($issues);

        unlink($certPath);
        unlink($keyPath);
    }

    public function testValidationFailsForMissingFiles(): void
    {
        $config = new MerchantConfig(
            'merchant.test',
            '/non/existent/cert.pem',
            '/non/existent/key.pem'
        );

        $issues = $config->validate();

        $this->assertCount(2, $issues);
        $this->assertStringContainsString('Certificate file not found', $issues[0]);
        $this->assertStringContainsString('Private key file not found', $issues[1]);
    }

    public function testValidationFailsForEmptyMerchantId(): void
    {
        $config = new MerchantConfig('', '/some/path', '/some/path');
        $issues = $config->validate();

        $this->assertContains('Merchant ID cannot be empty', $issues);
    }

    public function testReadOnlyProperties(): void
    {
        $config = new MerchantConfig('test', '/cert/path', '/key/path');

        $this->assertEquals('test', $config->merchantId);
        $this->assertEquals('/cert/path', $config->certificatePath);
        $this->assertEquals('/key/path', $config->privateKeyPath);
    }
}
