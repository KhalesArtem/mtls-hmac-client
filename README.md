# payment-mtls-hmac-client

## Install

```bash
composer require khalesartem/mtls-hmac-client:dev-main
```

## Usage

```php
use Payment\MtlsHmac\Client\GatewayClient;
use Payment\MtlsHmac\Config\GatewayConfig;

$config = new GatewayConfig(
    hmacSecret: 'my-secret',
    certPath: '/path/client-cert.pem',
    keyPath: '/path/client-key.pem',
    keyPassphrase: 'badssl.com', // optional
    verify: true,
    hmacAlgo: 'sha256',
    signatureHeader: 'X-Signature',
);

$client = new GatewayClient($config);
$response = $client->get('https://client.badssl.com/', [
    'transaction_id' => '12345',
    'amount' => '99.99',
    'currency' => 'USD',
]);

echo $response->getStatusCode();
echo (string) $response->getBody();
```

## Run tests

```bash
composer install
./vendor/bin/phpunit
```

Integration test uses `.env` (copy from `.env.example`) and will be **skipped** if required variables/files are missing.

## Environment Variables

Configure these variables in your `.env` file:

```env
GATEWAY_ENDPOINT=https://your-gateway.com/api
GATEWAY_HMAC_SECRET=your-hmac-secret
GATEWAY_CERT_PATH=/path/to/client-cert.pem
GATEWAY_KEY_PATH=/path/to/client-key.pem
GATEWAY_KEY_PASSPHRASE=optional-passphrase
GATEWAY_VERIFY=true
GATEWAY_HMAC_ALGO=sha256
GATEWAY_SIGNATURE_HEADER=X-Signature
```

## Example Script

Run the included example:

```bash
php example.php
```

This demonstrates both environment-based and manual configuration approaches.
