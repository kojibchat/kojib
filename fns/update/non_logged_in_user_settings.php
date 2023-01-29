<?php

if (!Registry::load('current_user')->logged_in) {

    $update_data = array();

    if (isset($data['color_scheme'])) {
        if ($data['color_scheme'] === "dark_mode") {
            $update_data['color_scheme'] = 'dark_mode';
        } else {
            $update_data['color_scheme'] = 'light_mode';
        }

        add_cookie('current_color_scheme', $update_data['color_scheme']);
    }

    if (isset($data['language_id'])) {
        $update_data['language_id'] = 0;
        $data['language_id'] = filter_var($data["language_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data['language_id'])) {

            $where = null;
            $where["languages.disabled"] = 0;
            $where["languages.language_id"] = $data['language_id'];
            $where["LIMIT"] = 1;

            $language = DB::connect()->select('languages', ['languages.language_id'], $where);

            if (isset($language[0])) {
                $update_data['language_id'] = $data['language_id'];
                add_cookie('current_language_id', $update_data['language_id']);
            }
        }
    }

}

$result = array();
$result['success'] = true;
$result['todo'] = 'refresh_current_page';