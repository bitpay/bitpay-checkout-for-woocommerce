<?php

use BitPayKeyUtils\KeyHelper\PrivateKey;
use BitPayKeyUtils\Storage\EncryptedFilesystemStorage;

require __DIR__ . '/vendor/autoload.php';

/**
 * Generate new private key for every new merchant.
 * Make sure you provide an easy recognizable name for each private key/Merchant
 * NOTE: In case you are providing the BitPay services to your clients,
 *       you MUST generate a different key per each of your clients
 *
 * WARNING: It is EXTREMELY IMPORTANT to place this key files in a very SECURE location
 **/
$privateKey = new PrivateKey(__DIR__ . '/secure/SecurePathPlusYourClientName.key');
$storageEngine = new EncryptedFilesystemStorage('YourMasterPassword');

try {
//  Use the EncryptedFilesystemStorage to load the Merchant's encrypted private key with the Master Password.
    $privateKey = $storageEngine->load(__DIR__ . '/secure/SecurePathPlusYourClientName.key');
} catch (Exception $ex) {
//  Check if the loaded keys is a valid key
    if (!$privateKey->isValid()) {
        $privateKey->generate();
    }

//  Encrypt and store it securely.
//  This Master password could be one for all keys or a different one for each merchant
    $storageEngine->persist($privateKey);
}

/**
 * Generate the public key from the private key every time (no need to store the public key).
 **/
try {
    $publicKey = $privateKey->getPublicKey();
} catch (Exception $ex) {
    echo $ex->getMessage();
}

/**
 * Derive the SIN from the public key.
 **/
$sin = $publicKey->getSin()->__toString();

/**
 * Use the SIN to request a pairing code and token.
 * The pairing code has to be approved in the BitPay Dashboard
 * THIS is just a cUrl example, which explains how to use the key pair for signing requests
 **/
$resourceUrl = 'https://test.bitpay.com/tokens';

$facade = 'merchant';

$postData = json_encode([
    'id' => $sin,
    'facade' => $facade
]);

$curlCli = curl_init($resourceUrl);

curl_setopt($curlCli, CURLOPT_HTTPHEADER, [
    'x-accept-version: 2.0.0',
    'Content-Type: application/json',
    'x-identity' => $publicKey->__toString(),
    'x-signature' => $privateKey->sign($resourceUrl . $postData),
]);

curl_setopt($curlCli, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curlCli, CURLOPT_POSTFIELDS, stripslashes($postData));
curl_setopt($curlCli, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($curlCli);
$resultData = json_decode($result, TRUE);
curl_close($curlCli);

if (array_key_exists('error', $resultData)) {
    echo $resultData['error'];
    exit;
}

/**
 * Example of a pairing Code returned from the BitPay API
 * which needs to be APPROVED on the BitPay Dashboard before being able to use it.
 **/
echo $resultData['data'][0]['pairingCode'];

/** End of request **/