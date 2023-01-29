<?php

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'customizer']])) {

    $content = '';

    if (isset($data['global_js']) && !empty($data['global_js'])) {
        $content = $data['global_js'];
    }

    $update = fopen("assets/js/common/custom_js.js", "w");
    fwrite($update, $content);
    fclose($update);

    $content = '';

    if (isset($data['custom_js_chat_page']) && !empty($data['custom_js_chat_page'])) {
        $content = $data['custom_js_chat_page'];
    }

    $update = fopen("assets/js/chat_page/custom_js.js", "w");
    fwrite($update, $content);
    fclose($update);

    $content = '';

    if (isset($data['custom_js_entry_page']) && !empty($data['custom_js_entry_page'])) {
        $content = $data['custom_js_entry_page'];
    }

    $update = fopen("assets/js/entry_page/custom_js.js", "w");
    fwrite($update, $content);
    fclose($update);

    $content = '';

    if (isset($data['custom_js_landing_page']) && !empty($data['custom_js_landing_page'])) {
        $content = $data['custom_js_landing_page'];
    }

    $update = fopen("assets/js/landing_page/custom_js.js", "w");
    fwrite($update, $content);
    fclose($update);



    cache(['rebuild' => 'settings']);
    cache(['rebuild' => 'js']);

    $result = array();
    $result['success'] = true;
    $result['todo'] = 'refresh';
}
?>