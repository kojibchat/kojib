<?php

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'core_settings']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    if (isset($data['rebuild']) && !empty($data['rebuild'])) {

        foreach ($data['rebuild'] as $rebuild) {
            if ($rebuild === 'style_sheets') {
                cache(['rebuild' => 'css_variables']);
                cache(['rebuild' => 'css']);
            } else if ($rebuild === 'javascript_files') {
                cache(['rebuild' => 'js']);
            } else if ($rebuild === 'sitemap') {
                cache(['rebuild' => 'sitemap']);
            } else if ($rebuild === 'web_app_manifest') {
                cache(['rebuild' => 'manifest']);
            } else if ($rebuild === 'core_settings') {
                cache(['rebuild' => 'settings']);
            } else if ($rebuild === 'languages') {
                cache(['rebuild' => 'languages']);
            } else if ($rebuild === 'site_roles') {
                cache(['rebuild' => 'site_roles']);
            } else if ($rebuild === 'group_roles') {
                cache(['rebuild' => 'group_roles']);
            }
        }

    }

    $result = array();
    $result['success'] = true;
    $result['todo'] = 'refresh';
}
?>