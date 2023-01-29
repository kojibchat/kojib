<?php

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'customizer']])) {

    $content = '';

    if (isset($data['global_css']) && !empty($data['global_css'])) {
        $content = $data['global_css'];
    }

    $update = fopen("assets/css/common/custom_css.css", "w");
    fwrite($update, $content);
    fclose($update);
    
    $content = '';

    if (isset($data['custom_css_chat_page']) && !empty($data['custom_css_chat_page'])) {
        $content = $data['custom_css_chat_page'];
    }

    $update = fopen("assets/css/chat_page/custom_css.css", "w");
    fwrite($update, $content);
    fclose($update);
    
    $content = '';

    if (isset($data['custom_css_entry_page']) && !empty($data['custom_css_entry_page'])) {
        $content = $data['custom_css_entry_page'];
    }

    $update = fopen("assets/css/entry_page/custom_css.css", "w");
    fwrite($update, $content);
    fclose($update);
    
    $content = '';

    if (isset($data['custom_css_landing_page']) && !empty($data['custom_css_landing_page'])) {
        $content = $data['custom_css_landing_page'];
    }

    $update = fopen("assets/css/landing_page/custom_css.css", "w");
    fwrite($update, $content);
    fclose($update);
    
    

    cache(['rebuild' => 'settings']);
    cache(['rebuild' => 'css']);

    $result = array();
    $result['success'] = true;
    $result['todo'] = 'refresh';
}
?>