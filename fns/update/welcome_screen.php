<?php

include 'fns/filters/load.php';
include 'fns/files/load.php';

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'core_settings']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    if (!isset($data['heading']) || empty($data['heading'])) {
        $result['error_variables'][] = ['heading'];
        $noerror = false;
    }

    if (!isset($data['message']) || empty($data['message'])) {
        $result['error_variables'][] = ['message'];
        $noerror = false;
    }

    if (!isset($data['footer_text']) || empty($data['footer_text'])) {
        $result['error_variables'][] = ['footer_text'];
        $noerror = false;
    }

    if ($noerror) {

        $language_id = Registry::load('current_user')->language;

        if (isset($data["language_id"])) {
            $data["language_id"] = filter_var($data["language_id"], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($data["language_id"])) {
                $language_id = $data["language_id"];
            }
        }

        language(['edit_string' => 'welcome_screen_heading', 'value' => $data['heading'], 'language_id' => $language_id]);
        language(['edit_string' => 'welcome_screen_message', 'value' => $data['message'], 'language_id' => $language_id]);
        language(['edit_string' => 'welcome_screen_footer_text', 'value' => $data['footer_text'], 'language_id' => $language_id]);

        if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
            if (isImage($_FILES['image']['tmp_name'])) {

                $welcome_image = 'assets/files/defaults/welcome.png';

                if (file_exists($welcome_image)) {
                    unlink($welcome_image);
                }

                if (files('upload', ['upload' => 'image', 'folder' => 'defaults', 'saveas' => 'welcome.png'])['result']) {
                    files('resize_img', ['resize' => 'defaults/welcome.png', 'width' => 200, 'height' => 200, 'crop' => false]);
                }
            }
        }

        $result['success'] = true;
        $result['todo'] = 'refresh';
    }
}