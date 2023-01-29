<?php

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'header_footer']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    if (!isset($data['page']) || empty(trim($data['page']))) {
        $result['error_variables'][] = ['page'];
        $noerror = false;
    }

    if ($noerror) {

        $page_elements = [
            'chat_page_header' => 'chat_page', 'chat_page_body' => 'chat_page', 'chat_page_footer' => 'chat_page',
            'entry_page_header' => 'entry_page', 'entry_page_body' => 'entry_page', 'entry_page_footer' => 'entry_page',
            'landing_page_header' => 'landing_page', 'landing_page_body' => 'landing_page', 'landing_page_footer' => 'landing_page',
        ];

        foreach ($page_elements as $page_element => $page) {
            if (isset($data[$page_element])) {
                $contents = '';
                $file_name = str_replace($page.'_', '', $page_element);
                $file = 'assets/headers_footers/'.$page.'/'.$file_name.'.php';

                if (!empty($data[$page_element])) {
                    $contents = $data[$page_element];
                }

                $build = fopen($file, "w");
                fwrite($build, $contents);
                fclose($build);
            }
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'refresh';

        $result['on_refresh'] = [
            'attributes' => [
                'class' => 'load_form',
                'form' => 'headers_footers'
            ]
        ];
    }
}
?>