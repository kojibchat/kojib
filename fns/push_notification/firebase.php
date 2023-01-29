<?php

$curl_url = "https://fcm.googleapis.com/fcm/send";

$subscription_key = "key=";
$subscription_key .= Registry::load('settings')->firebase_server_key;

$request_headers = array(
    "Authorization:" . $subscription_key,
    "Content-Type: application/json"
);

$postRequest = [
    "notification" => [
        "title" => $data['title'],
        "body" => $data['message'],
        "icon" => $data['image'],
        "click_action" => $data['url']
    ],
    'registration_ids' => $data['device_tokens']
];

$curl_request = curl_init();
curl_setopt($curl_request, CURLOPT_URL, $curl_url);
curl_setopt($curl_request, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl_request, CURLOPT_HEADER, FALSE);
curl_setopt($curl_request, CURLOPT_POST, TRUE);
curl_setopt($curl_request, CURLOPT_POSTFIELDS, json_encode($postRequest));
curl_setopt($curl_request, CURLOPT_HTTPHEADER, $request_headers);
curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, FALSE);

$response = curl_exec($curl_request);
curl_close($curl_request);

$result = array();
$result['response'] = $response;