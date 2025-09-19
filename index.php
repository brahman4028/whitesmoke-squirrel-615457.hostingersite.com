<?php

// Function to generate SHA-256 hash from API_KEY and SALT_KEY
function hashGenerator($data, $salt) {
    $combinedString = $data . $salt;
    return strtoupper(hash('sha256', $combinedString));
}

// Function to set the secret key (first 16 bytes of SHA-256 hash)
function setKey($secretKey) {
    $hash = hash('sha256', $secretKey, true);
    return substr($hash, 0, 16);
}

// Function to encrypt the data using AES-128-ECB mode
function encryptPayload($dataToEncrypt, $secretKey) {
    $jsonData = json_encode($dataToEncrypt);
    $key = setKey($secretKey);
    $paddedData = pkcs7Pad($jsonData, 16);
    $encryptedData = openssl_encrypt($paddedData, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    return base64_encode($encryptedData);
}

// PKCS7 padding function
function pkcs7Pad($data, $blockSize) {
    $padding = $blockSize - (strlen($data) % $blockSize);
    return $data . str_repeat(chr($padding), $padding);
}

// Example usage
$API_KEY = "689608649685";
$SALT_KEY = "0f95317aa7a7b2691132d790";
$API_URL  = "https://payinpg.starpg.co/api/v1/pg-hosted/";
$data = [
    "currency" => "INR",
    "merchant_ref" => strval(rand(1234523434, 6433234343443)),
    "amount" => "10",
    "method" => "HOSTED"
];

$secretKey = hashGenerator($API_KEY, $SALT_KEY);
$encryptedData = encryptPayload($data, $secretKey);

echo "<br>";
echo "<br>";

echo "Encrypted Data: " . $encryptedData . "\n";

echo "<br>";

$payload = [
    "data" => $encryptedData
];
$jsonPayload = json_encode($payload);

// 4. Curl request bhejna
$ch = curl_init($API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

// 5. Headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "API-KEY: " . $API_KEY
]);

// 6. Execute
$response = curl_exec($ch);

// 7. Error check
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
} else {
    echo "<h3>Raw Response:</h3>";
    echo "<pre>$response</pre>";

    $result = json_decode($response, true);
    echo "<h3>Parsed Response:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";

    if (isset($result['url'])) {
        echo "Payment URL: " . $result['url'];
        // header("Location: " . $result['url']);
    }
}
curl_close($ch);
zxzx