<?php
// API endpoint
$apiUrl = "https://payinpg.starpg.co/api/v1/pg-hosted/";

// API Key aur Salt (PG provider se milega)
$apiKey = "689608649685"; 
$salt   = "0f95317aa7a7b2691132d790";

// Step 1: Signature generate (API_KEY + SALT_KEY)
function hashGenerator($data, $salt) {
    $combinedString = $data . $salt;
    echo "<br>";
    echo $combinedString;
    echo "<br>";
    $combinedStringhash = hash('sha256', $combinedString);
     echo "<br>";
    echo $combinedStringhash;
    return strtoupper($combinedStringhash);
}

// Step 2: Secret key set karna (first 16 bytes of SHA-256 hash)
function setKey($secretKey) {
    $hash = hash('sha256', $secretKey, true); // raw binary
    echo "<br>";
    echo "<br> hash :";
    echo $hash;
    echo "<br>";
    echo "<br>substr: ";
    $setkeyvalue = substr($hash, 0, 16);
    echo $setkeyvalue;
    return $setkeyvalue;

}

// Step 3: PKCS7 padding function
function pkcs7Pad($data, $blockSize) {
    $pad = $blockSize - (strlen($data) % $blockSize);
    return $data . str_repeat(chr($pad), $pad);
}

// Step 4: Data ko AES-128-ECB se encrypt karna
function encryptPayload($dataToEncrypt, $secretKey) {
    $jsonData   = json_encode($dataToEncrypt);
    echo "<br>";
    echo "<br> json data :";
    echo $jsonData;
    echo "<br>";
    $key        = setKey($secretKey);
     echo "<br>";
    echo "<br> key :";
    echo $key;
    $paddedData = pkcs7Pad($jsonData, 16);
    echo "<br>";
    echo "<br> padded Data: ";
    echo $paddedData;

    $encryptedData = openssl_encrypt(
        $paddedData,
        'AES-128-ECB',
        $key,
        OPENSSL_RAW_DATA
    );

    return base64_encode($encryptedData); // API ko mostly base64 chahiye
}

// ----------------- MAIN FLOW -----------------

// Request body
$requestData = [
    "currency"     => "INR",
    "merchant_ref" => "MO2417251",
    "amount"       => "21",
    "method"       => "HOSTED"
];

// 1. Secret key banaye
$secretKey = hashGenerator($apiKey, $salt);

// 2. Payload ko encrypt karein
$encryptedData = encryptPayload($requestData, $secretKey);

echo "<h3>Encrypted Data:</h3>";
echo "<pre>$encryptedData</pre>";

// 3. API call ke liye final body
$payload = [
    "data" => $encryptedData
];
$jsonPayload = json_encode($payload);

// 4. Curl request bhejna
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);

// 5. Headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "API-KEY: " . $apiKey
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
?>
