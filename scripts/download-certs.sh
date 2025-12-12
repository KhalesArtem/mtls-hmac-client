#!/bin/bash

# Download BadSSL certificates for testing
CERTS_DIR="./certs"

echo "Downloading BadSSL certificates..."

# Create certs directory if it doesn't exist
mkdir -p "$CERTS_DIR"

# Try different certificate sources
CERT_DOWNLOADED=false
KEY_DOWNLOADED=false

# Try downloading from badssl.com repo on github
echo "Trying to download from BadSSL GitHub repo..."
curl -o "$CERTS_DIR/badssl.com-client-cert.pem" https://raw.githubusercontent.com/chromium/badssl.com/master/certs/sets/current/client-cert.pem 2>/dev/null
if [ $? -eq 0 ] && [ -s "$CERTS_DIR/badssl.com-client-cert.pem" ]; then
    echo "✅ Downloaded client certificate from GitHub"
    CERT_DOWNLOADED=true
fi

curl -o "$CERTS_DIR/badssl.com-client-key.pem" https://raw.githubusercontent.com/chromium/badssl.com/master/certs/sets/current/client-key.pem 2>/dev/null
if [ $? -eq 0 ] && [ -s "$CERTS_DIR/badssl.com-client-key.pem" ]; then
    echo "✅ Downloaded client private key from GitHub"
    KEY_DOWNLOADED=true
fi

# If GitHub download failed, create demo certificates
if [ "$CERT_DOWNLOADED" = false ] || [ "$KEY_DOWNLOADED" = false ]; then
    echo "⚠️  BadSSL certificates not available. Creating demo certificates..."
    
    # Create demo cert and key for testing (self-signed)
    openssl req -x509 -newkey rsa:2048 -keyout "$CERTS_DIR/badssl.com-client-key.pem" -out "$CERTS_DIR/badssl.com-client-cert.pem" -days 365 -nodes -subj "/CN=demo.client.local/O=Demo/C=US" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo "✅ Created demo client certificate and key"
        echo "⚠️  Note: This is a demo certificate for testing purposes only"
        echo "🔐 Certificate passphrase: (none - demo cert)"
    else
        echo "❌ Failed to create demo certificates"
        exit 1
    fi
else
    echo "🔐 Certificate passphrase: badssl.com"
fi

echo "📁 Certificates saved to: $CERTS_DIR/"
echo ""
echo "Files created:"
ls -la "$CERTS_DIR/"

# Verify certificate format
echo ""
echo "Certificate verification:"
if openssl x509 -in "$CERTS_DIR/badssl.com-client-cert.pem" -text -noout >/dev/null 2>&1; then
    echo "✅ Certificate format is valid"
else
    echo "❌ Certificate format is invalid"
    exit 1
fi

if openssl rsa -in "$CERTS_DIR/badssl.com-client-key.pem" -check -noout >/dev/null 2>&1; then
    echo "✅ Private key format is valid"
else
    echo "❌ Private key format is invalid"
    exit 1
fi