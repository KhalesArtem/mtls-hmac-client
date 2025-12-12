#!/bin/bash

echo "🚀 Running mTLS HMAC Client Tests..."
echo ""

# Check if certificates exist
if [ ! -f "./certs/badssl.com-client-cert.pem" ] || [ ! -f "./certs/badssl.com-client-key.pem" ]; then
    echo "❌ Certificates not found! Running download script..."
    ./scripts/download-certs.sh
    echo ""
fi

# Install dependencies if not present
if [ ! -d "./vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install
    echo ""
fi

# Run unit tests
echo "🧪 Running unit tests..."
./vendor/bin/phpunit tests/Unit/ --testdox
echo ""

# Run integration tests
echo "🌐 Running integration tests..."
./vendor/bin/phpunit tests/Integration/ --testdox
echo ""

echo "✅ Tests completed!"