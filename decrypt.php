<?php
// ----------------- Functions -----------------

// Secret Key banane ke liye API_KEY + SALT_KEY hash
function hashGenerator($data, $salt) {
    $combinedString = $data . $salt;
    return strtoupper(hash('sha256', $combinedString));
}

// First 16 bytes ka key generate
function setKey($secretKey) {
    $hash = hash('sha256', $secretKey, true); // raw binary
    return substr($hash, 0, 16);
}

// Unpadding (AES block padding remove)
function pkcs7Unpad($data) {
    $pad = ord($data[strlen($data) - 1]);
    if ($pad > strlen($data)) {
        return false;
    }
    return substr($data, 0, -1 * $pad);
}

// Decrypt function
function decryptPayload($encryptedData, $secretKey) {
    $key = setKey($secretKey);

    // Base64 decode
    $decodedData = base64_decode($encryptedData);

    // AES-128-ECB decryption
    $decryptedData = openssl_decrypt(
        $decodedData,
        'AES-128-ECB',
        $key,
        OPENSSL_RAW_DATA
    );

    // Unpad + JSON decode
    $unpadded = pkcs7Unpad($decryptedData);
    return json_decode($unpadded, true);
}

// ----------------- Variables -----------------

// API aur Salt (aapke PG se milega)
$apiKey = "689608649685"; 
$salt   = "0f95317aa7a7b2691132d790";

// Secret key generate
$secretKey = hashGenerator($apiKey, $salt);

// Agar form submit hua ho
$decryptedResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $encryptedData = trim($_POST['encryptedData']);
    if (!empty($encryptedData)) {
        $decryptedResult = decryptPayload($encryptedData, $secretKey);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Decrypt PG Response</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">

    <h2>PG Response Decryption Tool</h2>
    <form method="post">
        <label for="encryptedData">Paste Encrypted Data:</label><br>
        <textarea name="encryptedData" id="encryptedData" rows="6" cols="80" placeholder="Yaha base64 encrypted string paste karein..."><?php echo isset($_POST['encryptedData']) ? htmlspecialchars($_POST['encryptedData']) : ''; ?></textarea>
        <br><br>
        <button type="submit">Decrypt</button>
    </form>

    <?php if ($decryptedResult): ?>
        <h3>Decrypted Payload:</h3>
        <pre><?php print_r($decryptedResult); ?></pre>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <p style="color: red;">❌ Invalid or empty encrypted data</p>
    <?php endif; ?>

</body>
</html>
