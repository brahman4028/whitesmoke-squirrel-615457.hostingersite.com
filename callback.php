<?php
// Function: Generate 16-byte secret key from API + Salt
function set_key($my_key) {
    // SHA-256 hash generate karo
    $key = hash('sha256', $my_key, true);
    // First 16 bytes (128-bit) AES key
    return substr($key, 0, 16);
}

// Function: Decrypt the PG response
function decrypt_payload($response_string, $api_key, $salt_key) {
    // API Key + Salt Key ko combine karo
    $secret_key = set_key($api_key . $salt_key);

    // Base64 decode the response string
    $encrypted_bytes = base64_decode($response_string);

    // AES-128-ECB decryption
    $decrypted_bytes = openssl_decrypt(
        $encrypted_bytes,
        'aes-128-ecb',
        $secret_key,
        OPENSSL_RAW_DATA
    );

    return $decrypted_bytes;
}

// --------------------------
// Example usage in callback
// --------------------------

// 1. PG se aaya encrypted data (GET ya POST se)
if (isset($_GET['data'])) {
    $encrypted_response = $_GET['data'];   // callback se mila base64 string
    $api_key  = "YOUR_API_KEY_HERE";       // aapka actual API key
    $salt_key = "YOUR_SALT_KEY_HERE";      // aapka actual Salt key

    // 2. Decrypt the response
    $decrypted_response = decrypt_payload($encrypted_response, $api_key, $salt_key);

    // 3. JSON parse
    $decoded_json = json_decode($decrypted_response, true);

    // 4. Log / print for debugging
    echo "<h2>Payment Callback Response</h2>";
    echo "<h3>Encrypted (Base64):</h3><pre>$encrypted_response</pre>";
    echo "<h3>Decrypted (JSON):</h3><pre>$decrypted_response</pre>";

    echo "<h3>Parsed Array:</h3><pre>";
    print_r($decoded_json);
    echo "</pre>";

    // 5. Example: success/failure check
    if (isset($decoded_json['status']) && $decoded_json['status'] === "SUCCESS") {
        echo "<h2 style='color:green'>Payment Successful ✅</h2>";
        // Yaha DB me order ko "paid" mark karo
    } else {
        echo "<h2 style='color:red'>Payment Failed ❌</h2>";
        // Yaha DB me order ko "failed" mark karo
    }
} else {
    echo "No callback data received!";
}
?>
