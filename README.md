# Apple Pay Decoder for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/yousefkadah/apple-pay-decoder.svg?style=flat-square)](https://packagist.org/packages/yousefkadah/apple-pay-decoder)
[![Tests](https://img.shields.io/github/actions/workflow/status/yousefkadah/apple-pay-decoder/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/yousefkadah/apple-pay-decoder/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/yousefkadah/apple-pay-decoder.svg?style=flat-square)](https://packagist.org/packages/yousefkadah/apple-pay-decoder)
[![License](https://img.shields.io/packagist/l/yousefkadah/apple-pay-decoder.svg?style=flat-square)](https://packagist.org/packages/yousefkadah/apple-pay-decoder)

A production-ready PHP library for decrypting Apple Pay payment tokens using real cryptographic operations. This package implements the complete EC_v1 token decryption process including ECDH key agreement, KDF derivation, and AES-GCM decryption.

## Features

- ✅ **EC_v1 Token Support**: Full support for Apple Pay's EC_v1 encrypted payment tokens
- ✅ **Production Ready**: Real cryptographic operations using OpenSSL
- ✅ **Security First**: Proper validation and error handling
- ✅ **Framework Agnostic**: Works with any PHP framework or standalone
- ✅ **PSR-4 Compatible**: Modern PHP package structure
- ✅ **Well Tested**: Comprehensive test suite
- ✅ **Type Safe**: Full PHP 8+ type declarations

## Requirements

- PHP 8.0 or higher
- OpenSSL extension
- JSON extension

## Installation

You can install the package via composer:

```bash
composer require yousefkadah/apple-pay-decoder
```

## Quick Start

```php
<?php

use YousefKadah\ApplePayDecoder\ApplePayDecoder;
use YousefKadah\ApplePayDecoder\Config\MerchantConfig;

// Configure your merchant credentials
$config = new MerchantConfig(
    merchantId: 'merchant.com.yourcompany.app',
    certificatePath: '/path/to/merchant_certificate.pem',
    privateKeyPath: '/path/to/merchant_private_key.pem'
);

// Create decoder instance
$decoder = new ApplePayDecoder($config);

// Your Apple Pay token data
$paymentToken = [
    'version' => 'EC_v1',
    'data' => 'base64-encoded-encrypted-data...',
    'signature' => 'base64-encoded-signature...',
    'header' => [
        'ephemeralPublicKey' => 'base64-encoded-key...',
        'publicKeyHash' => 'base64-encoded-hash...',
        'transactionId' => 'hex-transaction-id...'
    ]
];

try {
    // Decrypt the payment token
    $decryptedData = $decoder->decrypt($paymentToken);
    
    // Access decrypted payment information
    echo "Card Number: " . $decryptedData['applicationPrimaryAccountNumber'];
    echo "Expiry: " . $decryptedData['applicationExpirationDate'];
    echo "Amount: " . $decryptedData['transactionAmount'];
    echo "Currency: " . $decryptedData['currencyCode'];
    
} catch (\Exception $e) {
    echo "Decryption failed: " . $e->getMessage();
}
```

## Advanced Usage

### Custom Logger

```php
use Psr\Log\LoggerInterface;

$decoder = new ApplePayDecoder($config, $logger);
```

### Validation Only

```php
// Validate configuration without decrypting
$issues = $decoder->validateConfiguration();
if (!empty($issues)) {
    foreach ($issues as $issue) {
        echo "Configuration issue: $issue\n";
    }
}
```

### Multiple Merchant Support

```php
// For handling multiple merchant configurations
$configs = [
    new MerchantConfig('merchant.primary', '/path/cert1.pem', '/path/key1.pem'),
    new MerchantConfig('merchant.secondary', '/path/cert2.pem', '/path/key2.pem'),
];

foreach ($configs as $config) {
    try {
        $decoder = new ApplePayDecoder($config);
        $result = $decoder->decrypt($paymentToken);
        break; // Success with this merchant
    } catch (\Exception $e) {
        continue; // Try next merchant
    }
}
```

## Decrypted Data Structure

The decrypted payment token contains the following information:

```php
[
    'applicationPrimaryAccountNumber' => '4111111111111111',  // Card number
    'applicationExpirationDate' => '251231',                 // YYMMDD format
    'currencyCode' => '840',                                 // ISO 4217 currency code
    'transactionAmount' => 1000,                             // Amount in smallest currency unit
    'deviceManufacturerIdentifier' => '040010030273',        // Device identifier
    'paymentDataType' => '3DSecure',                         // Payment data type
    'paymentData' => [
        'onlinePaymentCryptogram' => 'base64-cryptogram'     // 3DS cryptogram
    ]
]
```

## Apple Pay Setup

Before using this package, you need to set up Apple Pay merchant certificates:

1. **Apple Developer Account**: Enroll in Apple Developer Program
2. **Merchant ID**: Create a merchant identifier
3. **Payment Processing Certificate**: Generate and download the certificate
4. **Private Key**: Extract the private key from the certificate

For detailed setup instructions, visit [Apple's official documentation](https://developer.apple.com/documentation/passkit/apple_pay/).

## Security Considerations

- 🔒 **Certificate Security**: Store certificates securely and rotate regularly
- 🔒 **Private Key Protection**: Never expose private keys in version control
- 🔒 **Environment Variables**: Use environment variables for sensitive configuration
- 🔒 **Logging**: Avoid logging sensitive decrypted data in production
- 🔒 **Validation**: Always validate merchant configuration before processing

## Testing

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer phpstan

# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email your-email@example.com instead of using the issue tracker.

## Credits

- [Yousef Kadah](https://github.com/yousefkadah)
- [All Contributors](../../contributors)

Inspired by Apple's Payment Token Format Reference and various open-source implementations.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Support

- 📖 [Documentation](https://github.com/yousefkadah/apple-pay-decoder/wiki)
- 🐛 [Issue Tracker](https://github.com/yousefkadah/apple-pay-decoder/issues)
- 💬 [Discussions](https://github.com/yousefkadah/apple-pay-decoder/discussions)

---

Made with ❤️ for the PHP community
