<?php

$curl_url = 'https://api.webpushr.com/v1/notification/send/sid';

$http_header = array(
    "Content-Type: Application/Json",
    "webpushrKey: ".Registry::load('settings')->webpushr_rest_api_key,
    "webpushrAuthToken: ".Registry::load('settings')->webpushr_authentication_token
);

foreach ($data['device_tokens'] as $device_token) {

    $postRequest = array(
        'sid' => $device_token,
        'title' => $data['title'],
        'message' => $data['message'],
        'image' => $data['image'],
        'target_url' => $data['url']
    );


    $postRequest = json_encode($postRequest);

    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_URL, $curl_url);
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $http_header);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_request, CURLOPT_HEADER, FALSE);
    curl_setopt($curl_request, CURLOPT_POST, TRUE);
    curl_setopt($curl_request, CURLOPT_POSTFIELDS, $postRequest);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, FALSE);

    $response = curl_exec($curl_request);
    curl_close($curl_request);
}


$result = array();
$result['response'] = $response;