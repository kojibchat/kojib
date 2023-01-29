<?php

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'customizer']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    $all_css_variables = DB::connect()->select("css_variables", ["css_variable", "css_variable_value", "color_scheme"]);
    $stored_css_variables = array();

    foreach ($all_css_variables as $stored_css_variable) {
        $color_scheme = $stored_css_variable['color_scheme'];
        $variable = $stored_css_variable['css_variable'];
        $stored_css_variables[$color_scheme][$variable] = $stored_css_variable['css_variable_value'];
    }

    include('fns/global/css_variables.php');

    foreach ($css_variables as $variable_index => $css_variable) {
        foreach ($css_variable as $variable => $css_value) {

            $color_scheme = 'light_mode';
            $css_variable_name = $variable_index.'-'.$variable;
            $css_variable_value = $css_value;

            $post_variable = 'light-color-scheme-'.$css_variable_name;

            if (isset($data[$post_variable]) && !empty($data[$post_variable])) {
                $css_variable_value = htmlspecialchars($data[$post_variable], ENT_QUOTES, 'UTF-8');
            }

            if (!isset($stored_css_variables['light_mode'][$css_variable_name])) {
                DB::connect()->insert("css_variables", [
                    "css_variable" => $css_variable_name,
                    "css_variable_value" => $css_variable_value,
                    "color_scheme" => $color_scheme,
                    "updated_on" => Registry::load('current_user')->time_stamp
                ]);
            } else {
                DB::connect()->update("css_variables", [
                    "css_variable_value" => $css_variable_value,
                    "updated_on" => Registry::load('current_user')->time_stamp
                ], ["css_variable" => $css_variable_name, "color_scheme" => $color_scheme]);
            }
        }
    }


    include('fns/global/dark_mode_css_variables.php');

    foreach ($css_variables as $variable_index => $css_variable) {
        foreach ($css_variable as $variable => $css_value) {

            $color_scheme = 'dark_mode';
            $css_variable_name = $variable_index.'-'.$variable;
            $css_variable_value = $css_value;

            $post_variable = 'dark-color-scheme-'.$css_variable_name;

            if (isset($data[$post_variable]) && !empty($data[$post_variable])) {
                $css_variable_value = htmlspecialchars($data[$post_variable], ENT_QUOTES, 'UTF-8');
            }

            if (!isset($stored_css_variables['dark_mode'][$css_variable_name])) {
                DB::connect()->insert("css_variables", [
                    "css_variable" => $css_variable_name,
                    "css_variable_value" => $css_variable_value,
                    "color_scheme" => $color_scheme,
                    "updated_on" => Registry::load('current_user')->time_stamp
                ]);
            } else {
                DB::connect()->update("css_variables", [
                    "css_variable_value" => $css_variable_value,
                    "updated_on" => Registry::load('current_user')->time_stamp
                ], ["css_variable" => $css_variable_name, "color_scheme" => $color_scheme]);
            }
        }
    }

    if (isset($_FILES['chat_page_logo_light_mode']['name']) && !empty($_FILES['chat_page_logo_light_mode']['name'])) {
        if (isImage($_FILES['chat_page_logo_light_mode']['tmp_name'])) {

            $chat_page_logo_light_mode = 'assets/files/logos/chat_page_logo.png';

            if (file_exists($chat_page_logo_light_mode)) {
                unlink($chat_page_logo_light_mode);
            }

            if (files('upload', ['upload' => 'chat_page_logo_light_mode', 'folder' => 'logos', 'saveas' => 'chat_page_logo.png'])['result']) {
                files('resize_img', ['resize' => 'logos/chat_page_logo.png', 'width' => 150, 'height' => 150, 'crop' => false]);
            }
        }
    }


    if (isset($_FILES['chat_page_bg_light_mode']['name']) && !empty($_FILES['chat_page_bg_light_mode']['name'])) {
        if (isImage($_FILES['chat_page_bg_light_mode']['tmp_name'])) {

            $chat_page_bg_light_mode = 'assets/files/backgrounds/chat_page_bg.jpg';

            if (file_exists($chat_page_bg_light_mode)) {
                unlink($chat_page_bg_light_mode);
            }

            if (files('upload', ['upload' => 'chat_page_bg_light_mode', 'folder' => 'backgrounds', 'saveas' => 'chat_page_bg.jpg'])['result']) {
                files('resize_img', ['resize' => 'backgrounds/chat_page_bg.jpg', 'width' => 1920, 'height' => 1080, 'crop' => false]);
            }
        }
    }


    if (isset($_FILES['loading_image_light_mode']['name']) && !empty($_FILES['loading_image_light_mode']['name'])) {
        if (isImage($_FILES['loading_image_light_mode']['tmp_name'])) {

            $loading_image_light_mode = 'assets/files/defaults/loading_image_light_mode.png';

            if (file_exists($loading_image_light_mode)) {
                unlink($loading_image_light_mode);
            }

            if (files('upload', ['upload' => 'loading_image_light_mode', 'folder' => 'defaults', 'saveas' => 'loading_image_light_mode.png'])['result']) {
                files('resize_img', ['resize' => 'defaults/loading_image_light_mode.png', 'width' => 150, 'height' => 150, 'crop' => false]);
            }
        }
    }



    if (isset($_FILES['entry_page_logo_light_mode']['name']) && !empty($_FILES['entry_page_logo_light_mode']['name'])) {
        if (isImage($_FILES['entry_page_logo_light_mode']['tmp_name'])) {

            $entry_page_logo_light_mode = 'assets/files/logos/entry_page_logo.png';

            if (file_exists($entry_page_logo_light_mode)) {
                unlink($entry_page_logo_light_mode);
            }

            if (files('upload', ['upload' => 'entry_page_logo_light_mode', 'folder' => 'logos', 'saveas' => 'entry_page_logo.png'])['result']) {
                files('resize_img', ['resize' => 'logos/entry_page_logo.png', 'width' => 150, 'height' => 150, 'crop' => false]);
            }
        }
    }


    if (isset($_FILES['entry_page_bg_light_mode']['name']) && !empty($_FILES['entry_page_bg_light_mode']['name'])) {
        if (isImage($_FILES['entry_page_bg_light_mode']['tmp_name'])) {

            $entry_page_bg_light_mode = 'assets/files/backgrounds/entry_page_bg.jpg';

            if (file_exists($entry_page_bg_light_mode)) {
                unlink($entry_page_bg_light_mode);
            }

            if (files('upload', ['upload' => 'entry_page_bg_light_mode', 'folder' => 'backgrounds', 'saveas' => 'entry_page_bg.jpg'])['result']) {
                files('resize_img', ['resize' => 'backgrounds/entry_page_bg.jpg', 'width' => 1920, 'height' => 1080, 'crop' => false]);
            }
        }
    }


    if (isset($_FILES['landing_page_logo_light_mode']['name']) && !empty($_FILES['landing_page_logo_light_mode']['name'])) {
        if (isImage($_FILES['landing_page_logo_light_mode']['tmp_name'])) {

            $landing_page_logo_light_mode = 'assets/files/logos/landing_page_logo.png';

            if (file_exists($landing_page_logo_light_mode)) {
                unlink($landing_page_logo_light_mode);
            }

            if (files('upload', ['upload' => 'landing_page_logo_light_mode', 'folder' => 'logos', 'saveas' => 'landing_page_logo.png'])['result']) {
                files('resize_img', ['resize' => 'logos/landing_page_logo.png', 'width' => 150, 'height' => 150, 'crop' => false]);
            }
        }
    }

    if (isset($_FILES['landing_page_footer_logo_light_mode']['name']) && !empty($_FILES['landing_page_footer_logo_light_mode']['name'])) {
        if (isImage($_FILES['landing_page_footer_logo_light_mode']['tmp_name'])) {

            $landing_page_footer_logo_light_mode = 'assets/files/logos/landing_page_footer_logo.png';

            if (file_exists($landing_page_footer_logo_light_mode)) {
                unlink($landing_page_footer_logo_light_mode);
            }

            if (files('upload', ['upload' => 'landing_page_footer_logo_light_mode', 'folder' => 'logos', 'saveas' => 'landing_page_footer_logo.png'])['result']) {
                files('resize_img', ['resize' => 'logos/landing_page_footer_logo.png', 'width' => 150, 'height' => 150, 'crop' => false]);
            }
        }
    }

    if (isset($_FILES['chat_page_logo_dark_mode']['name']) && !empty($_FILES['chat_page_logo_dark_mode']['name'])) {
        if (isImage($_FILES['chat_page_logo_dark_mode']['tmp_name'])) {

            $chat_page_logo_dark_mode = 'assets/files/logos/chat_page_logo_dark_mode.png';

            if (file_exists($chat_page_logo_dark_mode)) {
                unlink($chat_page_logo_dark_mode);
            }

            if (files('upload', ['upload' => 'chat_page_logo_dark_mode', 'folder' => 'logos', 'saveas' => 'chat_page_logo_dark_mode.png'])['result']) {
                files('resize_img', ['resize' => 'logos/chat_page_logo_dark_mode.png', 'width' => 150, 'height' => 150, 'crop' => false]);
            }
        }
    }


    if (isset($_FILES['chat_page_bg_dark_mode']['name']) && !empty($_FILES['chat_page_bg_dark_mode']['name'])) {
        if (isImage($_FILES['chat_page_bg_dark_mode']['tmp_name'])) {

            $chat_page_bg_dark_mode = 'assets/files/backgrounds/chat_page_bg_dark_mode.jpg';

            if (file_exists($chat_page_bg_dark_mode)) {
                unlink($chat_page_bg_dark_mode);
            }

            if (files('upload', ['upload' => 'chat_page_bg_dark_mode', 'folder' => 'backgrounds', 'saveas' => 'chat_page_bg_dark_mode.jpg'])['result']) {
                files('resize_img', ['resize' => 'backgrounds/chat_page_bg_dark_mode.jpg', 'width' => 1920, 'height' => 1080, 'crop' => false]);
            }
        }
    }


    if (isset($_FILES['loading_image_dark_mode']['name']) && !empty($_FILES['loading_image_dark_mode']['name'])) {
        if (isImage($_FILES['loading_image_dark_mode']['tmp_name'])) {

            $loading_image_dark_mode = 'assets/files/defaults/loading_image_dark_mode.png';

            if (file_exists($loading_image_dark_mode)) {
                unlink($loading_image_dark_mode);
            }

            if (files('upload', ['upload' => 'loading_image_dark_mode', 'folder' => 'defaults', 'saveas' => 'loading_image_dark_mode.png'])['result']) {
                files('resize_img', ['resize' => 'defaults/loading_image_dark_mode.png', 'width' => 150, 'height' => 150, 'crop' => false]);
            }
        }
    }




    if (isset($_FILES['entry_page_logo_dark_mode']['name']) && !empty($_FILES['entry_page_logo_dark_mode']['name'])) {
        if (isImage($_FILES['entry_page_logo_dark_mode']['tmp_name'])) {

            $entry_page_logo_dark_mode = 'assets/files/logos/entry_page_logo_dark_mode.png';

            if (file_exists($entry_page_logo_dark_mode)) {
                unlink($entry_page_logo_dark_mode);
            }

            if (files('upload', ['upload' => 'entry_page_logo_dark_mode', 'folder' => 'logos', 'saveas' => 'entry_page_logo_dark_mode.png'])['result']) {
                files('resize_img', ['resize' => 'logos/entry_page_logo_dark_mode.png', 'width' => 150, 'height' => 150, 'crop' => false]);
            }
        }
    }


    if (isset($_FILES['entry_page_bg_dark_mode']['name']) && !empty($_FILES['entry_page_bg_dark_mode']['name'])) {
        if (isImage($_FILES['entry_page_bg_dark_mode']['tmp_name'])) {

            $entry_page_bg_dark_mode = 'assets/files/backgrounds/entry_page_bg_dark_mode.jpg';

            if (file_exists($entry_page_bg_dark_mode)) {
                unlink($entry_page_bg_dark_mode);
            }

            if (files('upload', ['upload' => 'entry_page_bg_dark_mode', 'folder' => 'backgrounds', 'saveas' => 'entry_page_bg_dark_mode.jpg'])['result']) {
                files('resize_img', ['resize' => 'backgrounds/entry_page_bg_dark_mode.jpg', 'width' => 1920, 'height' => 1080, 'crop' => false]);
            }
        }
    }

    if (isset($_FILES['landing_page_logo_dark_mode']['name']) && !empty($_FILES['landing_page_logo_dark_mode']['name'])) {
        if (isImage($_FILES['landing_page_logo_dark_mode']['tmp_name'])) {

            $landing_page_logo_dark_mode = 'assets/files/logos/landing_page_logo_dark_mode.png';

            if (file_exists($landing_page_logo_dark_mode)) {
                unlink($landing_page_logo_dark_mode);
            }

            if (files('upload', ['upload' => 'landing_page_logo_dark_mode', 'folder' => 'logos', 'saveas' => 'landing_page_logo_dark_mode.png'])['result']) {
                files('resize_img', ['resize' => 'logos/landing_page_logo_dark_mode.png', 'width' => 150, 'height' => 150, 'crop' => false]);
            }
        }
    }

    if (isset($_FILES['landing_page_footer_logo_dark_mode']['name']) && !empty($_FILES['landing_page_footer_logo_dark_mode']['name'])) {
        if (isImage($_FILES['landing_page_footer_logo_dark_mode']['tmp_name'])) {

            $landing_page_footer_logo_dark_mode = 'assets/files/logos/landing_page_footer_logo_dark_mode.png';

            if (file_exists($landing_page_footer_logo_dark_mode)) {
                unlink($landing_page_footer_logo_dark_mode);
            }

            if (files('upload', ['upload' => 'landing_page_footer_logo_dark_mode', 'folder' => 'logos', 'saveas' => 'landing_page_footer_logo_dark_mode.png'])['result']) {
                files('resize_img', ['resize' => 'logos/landing_page_footer_logo_dark_mode.png', 'width' => 150, 'height' => 150, 'crop' => false]);
            }
        }
    }

    if (isset($data['chat_page_boxed_layout']) && $data['chat_page_boxed_layout'] !== Registry::load('settings')->chat_page_boxed_layout) {
        DB::connect()->update("settings", ["value" => $data['chat_page_boxed_layout'], "updated_on" => Registry::load('current_user')->time_stamp], ["setting" => 'chat_page_boxed_layout']);
    }

    cache(['rebuild' => 'settings']);
    cache(['rebuild' => 'css_variables']);
    cache(['rebuild' => 'css']);

    $result = array();
    $result['success'] = true;
    $result['todo'] = 'refresh';

    $result['on_refresh'] = [
        'attributes' => [
            'class' => 'load_form',
            'form' => 'appearance'
        ]
    ];

}