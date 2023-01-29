<?php

$curl_url = "https://onesignal.com/api/v1/notifications";

$postRequest = array(
    'app_id' => Registry::load('settings')->onesignal_app_id,
    'include_player_ids' => $data['device_tokens'],
    'headings' => array('en' => $data['title']),
    'contents' => ['en' => $data['message']],
    'large_icon' => $data['image'],
    'url' => $data['url']
);


$postRequest = json_encode($postRequest);

$curl_request = curl_init();
curl_setopt($curl_request, CURLOPT_URL, $curl_url);

if (isset(Registry::load('settings')->onesignal_rest_api_key) && !empty(Registry::load('settings')->onesignal_rest_api_key)) {
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
        'Authorization: Basic '.Registry::load('settings')->onesignal_rest_api_key));
} else {
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
}

curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl_request, CURLOPT_HEADER, FALSE);
curl_setopt($curl_request, CURLOPT_POST, TRUE);
curl_setopt($curl_request, CURLOPT_POSTFIELDS, $postRequest);
curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, FALSE);

$response = curl_exec($curl_request);
curl_close($curl_request);

$result = array();
$result['response'] = $response;