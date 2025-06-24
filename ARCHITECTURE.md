# Modular Architecture Guide

## Overview

The Apple Pay Decoder package has been refactored into a professional, modular architecture that separates concerns and makes the code more maintainable, testable, and extensible.

## Architecture Components

### Core Service Layer
- **`ApplePayDecryptionService`** - Main orchestration service that coordinates all components
- **`ApplePayDecoder`** - Legacy compatibility wrapper (delegates to the service)

### Specialized Components

#### Cryptographic Operations (`src/Crypto/`)
- **`EcdhKeyAgreement`** - Handles ECDH key agreement operations
- **`KeyDerivationFunction`** - Implements KDF (Key Derivation Function)
- **`AesGcmDecryption`** - Handles AES-GCM decryption operations  
- **`PrivateKeyManager`** - Manages private key loading and validation

#### Data Processing (`src/Parser/`)
- **`TokenDataParser`** - Extracts and parses token components

#### Validation (`src/Validator/`)
- **`TokenValidator`** - Validates payment token structure and format
- **`SystemValidator`** - Validates system requirements and extensions

#### Configuration (`src/Config/`)
- **`MerchantConfig`** - Manages merchant configuration and validation

#### Exceptions (`src/Exceptions/`)
- **`ApplePayDecryptionException`** - Base exception
- **`CryptographicException`** - Cryptographic operation failures
- **`InvalidConfigurationException`** - Configuration issues
- **`InvalidTokenException`** - Token validation failures

### Facade Layer (`src/Facade/`)
- **`ApplePay`** - Simplified static interface for easy usage

## Usage Patterns

### 1. Simple Facade Usage (Recommended)
```php
use YousefKadah\ApplePayDecoder\Facade\ApplePay;

// One-time decryption
$result = ApplePay::decrypt($token, $merchantId, $certPath, $keyPath);

// Configure once, use many times
ApplePay::configure($merchantId, $certPath, $keyPath);
$result = ApplePay::quickDecrypt($token);
```

### 2. Environment-Based Configuration
```php
// Set environment variables
$_ENV['APPLE_PAY_MERCHANT_ID'] = 'merchant.com.example';
$_ENV['APPLE_PAY_CERT_PATH'] = '/path/to/cert.pem';
$_ENV['APPLE_PAY_KEY_PATH'] = '/path/to/key.pem';

// Configure and use
ApplePay::configureFromEnvironment();
$result = ApplePay::quickDecrypt($token);
```

### 3. Direct Service Usage
```php
use YousefKadah\ApplePayDecoder\ApplePayDecryptionService;
use YousefKadah\ApplePayDecoder\Config\MerchantConfig;

$config = new MerchantConfig($merchantId, $certPath, $keyPath);
$service = new ApplePayDecryptionService($config, $logger);
$result = $service->decrypt($token);
```

### 4. Laravel Integration
```php
// In a service provider
ApplePay::configureFromEnvironment();

// In a controller
public function processPayment(Request $request)
{
    $paymentData = $request->get('paymentData');
    $result = ApplePay::quickDecrypt($paymentData);
    // Process payment...
}
```

## Benefits of Modular Architecture

### 1. **Separation of Concerns**
- Each class has a single responsibility
- Cryptographic operations are isolated
- Validation logic is centralized
- Configuration is managed separately

### 2. **Testability**
- Each component can be unit tested independently
- Mock objects can be easily created for testing
- Components have well-defined interfaces

### 3. **Maintainability**
- Changes to one component don't affect others
- Code is easier to understand and debug
- New features can be added without touching existing code

### 4. **Extensibility**
- New cryptographic algorithms can be added easily
- Additional validators can be plugged in
- Different configuration sources can be supported

### 5. **Professional Standards**
- Follows PSR-4 autoloading standards
- Implements PSR-12 coding standards
- Uses proper exception handling
- Includes comprehensive documentation

## Component Interactions

```
ApplePay Facade
    ↓
ApplePayDecryptionService (Main Orchestrator)
    ├── MerchantConfig (Configuration)
    ├── TokenValidator (Input Validation)
    ├── SystemValidator (System Checks)
    ├── TokenDataParser (Data Extraction)
    ├── EcdhKeyAgreement (Key Agreement)
    ├── KeyDerivationFunction (Key Derivation)
    ├── AesGcmDecryption (Decryption)
    └── PrivateKeyManager (Key Management)
```

## Migration from Legacy Code

### Old Way (Before Refactoring)
```php
$decoder = new ApplePayDecoder($config);
$result = $decoder->decrypt($token);
```

### New Way (Recommended)
```php
// Option 1: Facade
$result = ApplePay::decrypt($token, $merchantId, $certPath, $keyPath);

// Option 2: Service
$service = new ApplePayDecryptionService($config);
$result = $service->decrypt($token);

// Option 3: Legacy compatibility (still works)
$decoder = new ApplePayDecoder($config);
$result = $decoder->decrypt($token);
```

## Error Handling

The modular architecture provides granular error handling:

```php
try {
    $result = ApplePay::quickDecrypt($token);
} catch (InvalidTokenException $e) {
    // Handle token validation errors
} catch (CryptographicException $e) {
    // Handle cryptographic operation errors
} catch (InvalidConfigurationException $e) {
    // Handle configuration issues
} catch (ApplePayDecryptionException $e) {
    // Handle any other Apple Pay related errors
}
```

## Validation and Diagnostics

```php
// System requirements check
$issues = ApplePay::validateSystem();
if (!empty($issues)) {
    foreach ($issues as $issue) {
        echo "System issue: $issue\n";
    }
}

// Configuration validation
$configIssues = ApplePay::validateConfiguration();
if (!empty($configIssues)) {
    foreach ($configIssues as $issue) {
        echo "Config issue: $issue\n";
    }
}
```

This modular architecture makes the Apple Pay Decoder package production-ready, maintainable, and suitable for enterprise-level applications.
