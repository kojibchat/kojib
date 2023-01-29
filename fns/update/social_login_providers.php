<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';
$result['error_variables'] = [];

if (role(['permissions' => ['social_login_providers' => 'edit']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    $noerror = true;
    $create_user = $disabled = $open_in_popup = 0;
    $provider_id = 0;

    $providers = [
        'Amazon' => 'Amazon', 'Authentiq' => 'Authentiq',
        'BitBucket' => 'BitBucket', 'Blizzard' => 'Blizzard', 'Discord' => 'Discord',
        'Disqus' => 'Disqus', 'Dribbble' => 'Dribbble', 'Dropbox' => 'Dropbox',
        'Facebook' => 'Facebook', 'Foursquare' => 'Foursquare', 'GitHub' => 'GitHub',
        'GitLab' => 'GitLab', 'Google' => 'Google', 'Instagram' => 'Instagram',
        'LinkedIn' => 'LinkedIn', 'Mailru' => 'Mailru', 'Medium' => 'Medium',
        'MicrosoftGraph' => 'MicrosoftGraph', 'Odnoklassniki' => 'Odnoklassniki',
        'ORCID' => 'ORCID', 'Paypal' => 'Paypal', 'Reddit	' => 'Reddit', 'Slack' => 'Slack',
        'Spotify' => 'Spotify', 'StackExchange' => 'StackExchange', 'Steam' => 'Steam',
        'Strava' => 'Strava', 'SteemConnect' => 'SteemConnect', 'Telegram' => 'Telegram',
        'Tumblr' => 'Tumblr', 'TwitchTV' => 'TwitchTV', 'Twitter' => 'Twitter',
        'Vkontakte' => 'Vkontakte', 'WeChat' => 'WeChat', 'WindowsLive' => 'WindowsLive',
        'WordPress' => 'WordPress', 'Yandex' => 'Yandex', 'Yahoo' => 'Yahoo', 'QQ' => 'QQ',
    ];

    if (!isset($data['identity_provider']) || empty($data['identity_provider'])) {
        $result['error_variables'][] = ['identity_provider'];
        $noerror = false;
    } else if (!in_array($data['identity_provider'], array_keys($providers))) {
        $result['error_variables'][] = ['identity_provider'];
        $noerror = false;
    }

    if (isset($data['social_login_provider_id'])) {
        $provider_id = filter_var($data["social_login_provider_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if ($noerror && !empty($provider_id)) {

        $data['identity_provider'] = htmlspecialchars($data['identity_provider'], ENT_QUOTES, 'UTF-8');
        $data['app_id'] = trim($data['app_id']);
        $data['app_key'] = trim($data['app_key']);
        $data['secret_key'] = trim($data['secret_key']);

        if (isset($data['disabled']) && $data['disabled'] === 'yes') {
            $disabled = 1;
        }

        if (isset($data['create_user']) && $data['create_user'] === 'yes') {
            $create_user = 1;
        }

        if (isset($data['open_in_popup']) && $data['open_in_popup'] === 'yes') {
            $open_in_popup = 1;
        }

        DB::connect()->update("social_login_providers", [
            "identity_provider" => $data['identity_provider'],
            "app_id" => $data['app_id'],
            "app_key" => $data['app_key'],
            "secret_key" => $data['secret_key'],
            "open_in_popup" => $open_in_popup,
            "disabled" => $disabled,
            "create_user" => $create_user,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ], ["social_login_provider_id" => $provider_id]);

        if (!DB::connect()->error) {

            if (isset($_FILES['icon']['name']) && !empty($_FILES['icon']['name'])) {

                include 'fns/filters/load.php';
                include 'fns/files/load.php';

                if (isImage($_FILES['icon']['tmp_name'])) {

                    foreach (glob("assets/files/social_login/".$provider_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                        unlink($oldimage);
                    }

                    $extension = pathinfo($_FILES['icon']['name'])['extension'];
                    $filename = $provider_id.Registry::load('config')->file_seperator.random_string(['length' => 6]).'.'.$extension;
                    if (files('upload', ['upload' => 'icon', 'folder' => 'social_login', 'saveas' => $filename])['result']) {
                        files('resize_img', ['resize' => 'social_login/'.$filename, 'width' => 150, 'height' => 150, 'crop' => true]);
                    }
                }
            }

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'social_login_providers';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }

    }
}

?>