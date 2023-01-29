<?php

$user_id = Registry::load('current_user')->id;

if (!empty($user_id)) {

    $update_data = ['updated_on' => Registry::load('current_user')->time_stamp];

    if (isset($data['offline_mode'])) {
        if (role(['permissions' => ['profile' => 'go_offline']])) {
            if ($data['offline_mode'] === "go_offline") {
                $update_data['offline_mode'] = 1;
            } else {
                $update_data['offline_mode'] = 0;
            }
        }
    }

    if (isset($data['color_scheme'])) {
        if (role(['permissions' => ['profile' => 'switch_color_scheme']])) {
            if ($data['color_scheme'] === "dark_mode") {
                $update_data['color_scheme'] = 'dark_mode';
            } else {
                $update_data['color_scheme'] = 'light_mode';
            }
        }
    }

    if (isset($data['language_id'])) {
        if (role(['permissions' => ['profile' => 'switch_languages']])) {
            $update_data['language_id'] = null;
            $data['language_id'] = filter_var($data["language_id"], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($data['language_id'])) {

                $where = null;
                $where["languages.disabled"] = 0;
                $where["languages.language_id"] = $data['language_id'];
                $where["LIMIT"] = 1;

                $language = DB::connect()->select('languages', ['languages.language_id'], $where);

                if (isset($language[0])) {
                    $update_data['language_id'] = $data['language_id'];
                }
            }
        }
    }

    DB::connect()->update("site_users_settings", $update_data, ["user_id" => $user_id]);
}

$result = array();
$result['success'] = true;
$result['todo'] = 'refresh';