<?php

if (Registry::load('settings')->people_nearby_feature === 'enable') {
    $user_latitude = Registry::load('current_user')->geo_latitude;
    $user_longitude = Registry::load('current_user')->geo_longitude;

    if (!empty($user_latitude) && !empty($user_longitude)) {
        if (!empty($user_latitude) && !empty($user_longitude)) {
            $private_data["nearby_users"] = true;
            include('fns/load/site_users.php');
        }
    }
}

?>