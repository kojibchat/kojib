<?php

include 'fns/filters/load.php';

$user_id = Registry::load('current_user')->id;

if (!empty($user_id)) {

    $update_data = ['updated_on' => Registry::load('current_user')->time_stamp];

    if (isset($data['latitude']) && isset($data['longitude'])) {

        $update_data['geo_latitude'] = $data['latitude'];
        $update_data['geo_longitude'] = $data['longitude'];

        DB::connect()->update("site_users", $update_data, ["user_id" => $user_id]);
    }
}

$result = array();
$result['success'] = true;
$result['todo'] = 'refresh';