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

### Method 1: Using the Facade (Easiest)

```php
<?php

use YousefKadah\ApplePayDecoder\Facade\ApplePay;

// One-time decryption
$decryptedData = ApplePay::decrypt(
    $paymentToken,
    'merchant.com.yourcompany.app',
    '/path/to/merchant_certificate.pem',
    '/path/to/merchant_private_key.pem'
);

// Or configure once, use multiple times
ApplePay::configure(
    'merchant.com.yourcompany.app',
    '/path/to/merchant_certificate.pem',
    '/path/to/merchant_private_key.pem'
);

$decryptedData = ApplePay::quickDecrypt($paymentToken);
```

### Method 2: Using Environment Variables

```php
<?php

use YousefKadah\ApplePayDecoder\Facade\ApplePay;

// Set environment variables
$_ENV['APPLE_PAY_MERCHANT_ID'] = 'merchant.com.yourcompany.app';
$_ENV['APPLE_PAY_CERT_PATH'] = '/path/to/merchant_certificate.pem';
$_ENV['APPLE_PAY_KEY_PATH'] = '/path/to/merchant_private_key.pem';

// Configure from environment
ApplePay::configureFromEnvironment();

// Use it
$decryptedData = ApplePay::quickDecrypt($paymentToken);
```

### Method 3: Using the Service Class Directly

```php
<?php

use YousefKadah\ApplePayDecoder\ApplePayDecryptionService;
use YousefKadah\ApplePayDecoder\Config\MerchantConfig;

// Configure your merchant credentials
$config = new MerchantConfig(
    merchantId: 'merchant.com.yourcompany.app',
    certificatePath: '/path/to/merchant_certificate.pem',
    privateKeyPath: '/path/to/merchant_private_key.pem'
);

// Create service instance
$service = new ApplePayDecryptionService($config);

// Decrypt the payment token
$decryptedData = $service->decrypt($paymentToken);

// Access decrypted payment information
echo "Card Number: " . $decryptedData['applicationPrimaryAccountNumber'];
echo "Expiry: " . $decryptedData['applicationExpirationDate'];
echo "Amount: " . $decryptedData['transactionAmount'];
echo "Currency: " . $decryptedData['currencyCode'];
```

## Laravel Integration

For Laravel applications, you can easily integrate the facade:

```php
<?php

// In a service provider or controller
use YousefKadah\ApplePayDecoder\Facade\ApplePay;

class PaymentController extends Controller
{
    public function processApplePayment(Request $request)
    {
        // Configure once in service provider
        ApplePay::configure(
            config('applepay.merchant_id'),
            config('applepay.cert_path'),
            config('applepay.key_path')
        );

        // Use in controllers
        $paymentData = $request->get('paymentData');
        $decryptedData = ApplePay::quickDecrypt($paymentData);
        
        // Process payment...
        return response()->json(['status' => 'success']);
    }
}
```

## Configuration Management

### Environment Variables
Set these environment variables for easy configuration:

```bash
APPLE_PAY_MERCHANT_ID=merchant.com.yourcompany.app
APPLE_PAY_CERT_PATH=/path/to/merchant_certificate.pem
APPLE_PAY_KEY_PATH=/path/to/merchant_private_key.pem
```

Then use:
```php
ApplePay::configureFromEnvironment();
$result = ApplePay::quickDecrypt($paymentToken);
```

## Advanced Usage

### Custom Logger

```php
use Psr\Log\LoggerInterface;
use YousefKadah\ApplePayDecoder\ApplePayDecryptionService;

$service = new ApplePayDecryptionService($config, $logger);
```

### Legacy Compatibility

```php
// For backward compatibility, the old ApplePayDecoder still works
use YousefKadah\ApplePayDecoder\ApplePayDecoder;

$decoder = new ApplePayDecoder($config);
$result = $decoder->decrypt($paymentToken);
```

### Component Usage (Advanced)

```php
// Use individual components for custom implementations
use YousefKadah\ApplePayDecoder\Crypto\EcdhKeyAgreement;
use YousefKadah\ApplePayDecoder\Crypto\KeyDerivationFunction;
use YousefKadah\ApplePayDecoder\Crypto\AesGcmDecryption;

$ecdh = new EcdhKeyAgreement($logger);
$kdf = new KeyDerivationFunction($logger, $merchantId);
$aes = new AesGcmDecryption($logger);

// Perform individual cryptographic operations...
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
