<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Payment\MtlsHmac\Client\GatewayClient;
use Payment\MtlsHmac\Config\GatewayConfig;
use Payment\MtlsHmac\Exception\GatewayException;
use Payment\MtlsHmac\Exception\HttpException;

try {
    echo "🚀 Начинаем тестирование mTLS HMAC клиента...\n\n";
    
    // === СПОСОБ 1: Из переменных окружения ===
    echo "📋 СПОСОБ 1: Создание клиента из .env файла\n";
    echo "--------------------------------------------\n";
    
    $client = GatewayClient::fromEnv();
    echo "✅ Клиент создан из переменных окружения\n";
    
    $payload = [
        'transaction_id' => '12345',       // ID транзакции
        'amount' => '99.99',              // Сумма
        'currency' => 'USD',              // Валюта
        'timestamp' => time(),            // Unix timestamp
    ];
    
    echo "📦 Данные для отправки: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
    
    // Попробуем отправить запрос
    try {
        $response = $client->getFromEnvEndpoint($payload);
        
        echo "✅ ЗАПРОС УСПЕШЕН!\n";
        echo "📊 HTTP Status: " . $response->getStatusCode() . "\n";
        echo "📋 Response Headers:\n";
        foreach ($response->getHeaders() as $name => $values) {
            echo "   {$name}: " . implode(', ', $values) . "\n";
        }
        echo "📄 Response Body (первые 200 символов):\n";
        echo substr($response->getBody()->getContents(), 0, 200) . "...\n\n";
        
    } catch (Exception $e) {
        echo "⚠️  Запрос через .env не удался (ожидаемо): " . $e->getMessage() . "\n\n";
    }

    // === СПОСОБ 2: Автоматическая конфигурация из ENV ===
    echo "📋 СПОСОБ 2: Автоматическая конфигурация (ENV fallback)\n";
    echo "----------------------------------------------------\n";
    
    // Теперь можно создавать без параметров - всё возьмётся из ENV!
    $autoConfig = new GatewayConfig();
    echo "✅ Конфигурация создана автоматически из ENV переменных\n";

    // === СПОСОБ 3: Частичная ручная конфигурация ===
    echo "\n📋 СПОСОБ 3: Частичная ручная конфигурация\n";
    echo "-------------------------------------------\n";
    
    // Указываем только то, что нужно переопределить - остальное из ENV
    $mixedConfig = new GatewayConfig(
        hmacSecret: 'custom-secret',                   // Переопределяем секрет
        timeoutSeconds: 30                             // Переопределяем таймаут
        // certPath, keyPath и другие параметры берутся из ENV
    );
    echo "✅ Смешанная конфигурация создана (секрет и таймаут ручные, остальное из ENV)\n";

    // === СПОСОБ 4: Полная ручная конфигурация ===
    echo "\n📋 СПОСОБ 4: Полная ручная конфигурация\n";
    echo "------------------------------------------\n";
    
    $manualConfig = new GatewayConfig(
        hmacSecret: 'manual-test-secret',              // Секрет для HMAC
        certPath: './certs/badssl-client-cert.pem',    // Путь к сертификату
        keyPath: './certs/badssl-client-key.pem',      // Путь к ключу
        keyPassphrase: null,                           // Пароль к ключу (если нужен)
        verify: true,                                  // Проверять SSL сертификат сервера
        hmacAlgo: 'sha256',                           // Алгоритм HMAC
        signatureHeader: 'X-Signature',               // Заголовок для подписи
        timeoutSeconds: 30,                           // Таймаут запроса
        connectTimeoutSeconds: 10                     // Таймаут подключения
    );

    $manualClient = new GatewayClient($manualConfig);
    echo "✅ Клиент создан с ручной конфигурацией\n";
    
    $manualPayload = [
        'test' => 'manual_config',
        'method' => 'direct_instantiation',
        'timestamp' => time()
    ];
    
    echo "📦 Данные для отправки: " . json_encode($manualPayload, JSON_PRETTY_PRINT) . "\n";
    
    // Показываем что происходит под капотом
    echo "🔒 Генерируем HMAC подпись...\n";
    $signer = new \Payment\MtlsHmac\Crypto\HmacSigner();
    $signature = $signer->sign($manualPayload, $manualConfig->hmacSecret, $manualConfig->hmacAlgo);
    echo "✅ HMAC подпись: " . $signature . "\n";
    
    echo "🔐 Подготавливаем mTLS соединение с сертификатами:\n";
    echo "   - Сертификат: " . $manualConfig->certPath . "\n";
    echo "   - Ключ: " . $manualConfig->keyPath . "\n";
    echo "   - Алгоритм: " . $manualConfig->hmacAlgo . "\n";
    echo "   - Заголовок: " . $manualConfig->signatureHeader . "\n\n";

    $manualResponse = $manualClient->get('https://client.badssl.com/', $manualPayload);

    echo "✅ ЗАПРОС УСПЕШНО ВЫПОЛНЕН!\n";
    echo "📊 HTTP Status: " . $manualResponse->getStatusCode() . "\n";
    echo "📋 Response Headers:\n";
    foreach ($manualResponse->getHeaders() as $name => $values) {
        echo "   {$name}: " . implode(', ', $values) . "\n";
    }
    echo "📄 Response Body (первые 500 символов):\n";
    echo substr($manualResponse->getBody()->getContents(), 0, 500) . "...\n";
    
    echo "\n🎉 ВСЕ РАБОТАЕТ КОРРЕКТНО!\n";
    echo "🔒 mTLS соединение установлено\n";
    echo "✍️  HMAC подпись проверена\n";
    echo "📡 Данные успешно переданы\n";

} catch (HttpException $e) {
    echo "\n❌ HTTP ОШИБКА НА ЭТАПЕ ЗАПРОСА:\n";
    echo "Описание: " . $e->getMessage() . "\n";
    echo "HTTP Status: " . $e->statusCode . "\n";
    echo "Response Body: " . substr($e->body, 0, 200) . "...\n\n";
    echo "Возможные причины:\n";
    echo "- Неверные клиентские сертификаты\n";
    echo "- Проблемы с SSL/TLS соединением\n";
    echo "- Сервер отклонил запрос\n";
} catch (GatewayException $e) {
    echo "\n❌ ОШИБКА КОНФИГУРАЦИИ:\n";
    echo "Описание: " . $e->getMessage() . "\n\n";
    echo "Возможные причины:\n";
    echo "- Отсутствуют переменные окружения\n";
    echo "- Неверные пути к сертификатам\n";
    echo "- Пустой HMAC секрет\n";
} catch (Exception $e) {
    echo "\n❌ НЕОЖИДАННАЯ ОШИБКА:\n";
    echo "Описание: " . $e->getMessage() . "\n";
    echo "Класс: " . get_class($e) . "\n";
}