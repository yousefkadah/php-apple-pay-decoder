<?php

require_once __DIR__ . '/vendor/autoload.php';

use YousefKadah\ApplePayDecoder\ApplePayDecoder;
use YousefKadah\ApplePayDecoder\Config\MerchantConfig;

// Example usage of the Apple Pay Decoder
try {
    // Configure your merchant credentials
    $config = new MerchantConfig(
        merchantId: 'merchant.com.yourcompany.app',
        certificatePath: __DIR__ . '/path/to/merchant_certificate.pem',
        privateKeyPath: __DIR__ . '/path/to/merchant_private_key.pem'
    );

    // Create decoder instance
    $decoder = new ApplePayDecoder($config);

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

    // Decrypt the payment token
    $decryptedData = $decoder->decrypt($paymentToken);

    // Display results
    echo "✅ Decryption successful!\n\n";
    echo "Card Number: " . $decryptedData['applicationPrimaryAccountNumber'] . "\n";
    echo "Expiry Date: " . $decryptedData['applicationExpirationDate'] . "\n";
    echo "Transaction Amount: " . $decryptedData['transactionAmount'] . "\n";
    echo "Currency Code: " . $decryptedData['currencyCode'] . "\n";
    echo "Device Manufacturer ID: " . $decryptedData['deviceManufacturerIdentifier'] . "\n";
    echo "Payment Data Type: " . $decryptedData['paymentDataType'] . "\n";

    if (isset($decryptedData['paymentData']['onlinePaymentCryptogram'])) {
        echo "Online Payment Cryptogram: " . $decryptedData['paymentData']['onlinePaymentCryptogram'] . "\n";
    }

} catch (\YousefKadah\ApplePayDecoder\Exceptions\InvalidConfigurationException $e) {
    echo "❌ Configuration Error: " . $e->getMessage() . "\n";
    echo "Please check your merchant certificate and private key paths.\n";
} catch (\YousefKadah\ApplePayDecoder\Exceptions\InvalidTokenException $e) {
    echo "❌ Token Error: " . $e->getMessage() . "\n";
    echo "Please check your payment token format.\n";
} catch (\YousefKadah\ApplePayDecoder\Exceptions\CryptographicException $e) {
    echo "❌ Cryptographic Error: " . $e->getMessage() . "\n";
    echo "Please check your certificates and token data.\n";
} catch (\Exception $e) {
    echo "❌ Unexpected Error: " . $e->getMessage() . "\n";
}
