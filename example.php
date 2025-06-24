<?php

require_once __DIR__ . '/vendor/autoload.php';

use YousefKadah\ApplePayDecoder\ApplePayDecryptionService;
use YousefKadah\ApplePayDecoder\Config\MerchantConfig;
use YousefKadah\ApplePayDecoder\Facade\ApplePay;

echo "=== Apple Pay Decoder Examples ===\n\n";

// Example 1: Using the main service class (Recommended)
echo "1. Using ApplePayDecryptionService (Recommended):\n";
try {
    $config = new MerchantConfig(
        merchantId: 'merchant.com.yourcompany.app',
        certificatePath: __DIR__ . '/path/to/merchant_certificate.pem',
        privateKeyPath: __DIR__ . '/path/to/merchant_private_key.pem'
    );

    $service = new ApplePayDecryptionService($config);

    // Example Apple Pay token (replace with real token data)
    $paymentToken = [
        'version' => 'EC_v1',
        'data' => 'base64-encoded-encrypted-data...',
        'signature' => 'base64-encoded-signature...',
        'header' => [
            'ephemeralPublicKey' => 'base64-encoded-ephemeral-public-key...',
            'publicKeyHash' => 'base64-encoded-public-key-hash...',
            'transactionId' => 'hex-transaction-id...'
        ]
    ];

    // $decryptedData = $service->decrypt($paymentToken);
    echo "✅ Service created successfully\n";

} catch (\Exception $e) {
    echo "❌ Service Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 2: Using the Facade for quick operations
echo "2. Using Facade for quick operations:\n";
try {
    // Quick decrypt with minimal setup
    /* $decryptedData = ApplePay::decrypt(
        $paymentToken,
        'merchant.com.yourcompany.app',
        '/path/to/merchant_certificate.pem',
        '/path/to/merchant_private_key.pem'
    ); */
    
    echo "✅ Facade method available\n";

} catch (\Exception $e) {
    echo "❌ Facade Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 3: Using Facade with default service
echo "3. Using Facade with default service:\n";
try {
    // Set up default service
    $defaultService = ApplePay::createService(
        'merchant.com.yourcompany.app',
        '/path/to/cert.pem',
        '/path/to/key.pem'
    );
    
    ApplePay::setDefaultService($defaultService);
    
    // Now you can use quick decrypt
    // $result = ApplePay::quickDecrypt($paymentToken);
    
    echo "✅ Default service configured\n";

} catch (\Exception $e) {
    echo "❌ Default Service Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 4: Using environment variables
echo "4. Using environment variables:\n";
try {
    // Set environment variables first
    $_ENV['APPLE_PAY_MERCHANT_ID'] = 'merchant.com.yourcompany.app';
    $_ENV['APPLE_PAY_CERT_PATH'] = '/path/to/cert.pem';
    $_ENV['APPLE_PAY_KEY_PATH'] = '/path/to/key.pem';
    
    // $service = ApplePay::fromEnvironment();
    // $result = $service->decrypt($paymentToken);
    
    echo "✅ Environment configuration available\n";

} catch (\Exception $e) {
    echo "❌ Environment Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 5: Legacy compatibility
echo "5. Legacy ApplePayDecoder compatibility:\n";
try {
    use YousefKadah\ApplePayDecoder\ApplePayDecoder;
    
    $config = new MerchantConfig(
        'merchant.com.yourcompany.app',
        '/path/to/cert.pem',
        '/path/to/key.pem'
    );
    
    $decoder = new ApplePayDecoder($config);
    // $result = $decoder->decrypt($paymentToken);
    
    echo "✅ Legacy decoder still works\n";

} catch (\Exception $e) {
    echo "❌ Legacy Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 6: Validation
echo "6. Configuration validation:\n";
try {
    $validationIssues = ApplePay::validateSystem();
    
    if (empty($validationIssues)) {
        echo "✅ System validation passed\n";
    } else {
        echo "⚠️  System issues found:\n";
        foreach ($validationIssues as $issue) {
            echo "   - $issue\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Validation Error: " . $e->getMessage() . "\n";
}

echo "\n=== Examples completed ===\n";
