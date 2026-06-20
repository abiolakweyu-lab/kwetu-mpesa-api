<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. SAFARICOM DARAJA CREDENTIALS (EXACT MATCH & SANITIZED)
$consumerKey    = trim('CMadUmISBbs7dXIUYgPoeP1vSD3JHzAraYtUGSiHGHzYsNf2'); 
$consumerSecret = trim('M4TmxaNtELzQV3KOUGb1Np6bjPENKpccZV4Ziyg2EKRtWBfG8VASbEoisVDLwqXJ'); 
$passkey        = trim('bfb2a54f3a3c1c57a9e3d3ff346550df4714db5328766431b5fb8b4303c4b964'); 

// 2. TRANSACTION DETAILS
$businessShortCode = '174379'; 
$amount            = '1';      
$partyA            = '254797688007'; 
$partyB            = '174379';
$phoneNumber       = $partyA;
$timestamp         = date('YmdHis');

// 3. GENERATE LIPA NA M-PESA PASSWORD
$password = base64_encode($businessShortCode . $passkey . $timestamp);

// 4. GENERATE ACCESS TOKEN FROM SAFARICOM
$url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$authCredentials = base64_encode($consumerKey . ':' . $consumerSecret);

$headers = [
    'Content-Type: application/json',
    'Authorization: Basic ' . $authCredentials
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_HEADER, FALSE);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); 
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); 

$result = curl_exec($curl);

if (curl_errno($curl)) {
    $error_msg = curl_error($curl);
    die("cURL Diagnostic Error: " . $error_msg);
}
curl_close($curl);

$jsonResult = json_decode($result);
$accessToken = $jsonResult->access_token ?? null;

if (!$accessToken) {
    die("Error: Token generation failed. Raw Server Response: " . htmlspecialchars($result));
}

// 5. INITIATE M-PESA EXPRESS STK PUSH
$stkUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$callbackUrl = 'https://webhook.site/0d2fc214-ba36-47a7-897f-442b100bbda1'; 

$stkHeaders = [
    'Content-Type:application/json',
    'Authorization:Bearer ' . $accessToken
];

$curl_post_data = [
    'BusinessShortCode' => $businessShortCode,
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerPayBillOnline',
    'Amount'            => $amount,
    'PartyA'            => $partyA,
    'PartyB'            => $partyB,
    'PhoneNumber'       => $phoneNumber,
    'CallBackURL'       => $callbackUrl,
    'AccountReference'  => 'KwetuWiFi',
    'TransactionDesc'   => 'WiFi Internet Access'
];

$data_string = json_encode($curl_post_data);

$curl = curl_init($stkUrl);
curl_setopt($curl, CURLOPT_URL, $stkUrl);
curl_setopt($curl, CURLOPT_HTTPHEADER, $stkHeaders);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

$stkResult = curl_exec($curl);
curl_close($curl);

header('Content-Type: application/json');
echo $stkResult;
?>
