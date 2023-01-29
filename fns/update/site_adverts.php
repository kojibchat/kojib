<?php

if (role(['permissions' => ['site_adverts' => 'edit']])) {

    include 'fns/filters/load.php';
    $result = array();
    $noerror = true;
    $disabled = $group_id = $advert_id = 0;
    $result['success'] = false;
    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    $advert_placements = [
        'left_content_block', 'entry_page_form_header',
        'entry_page_form_footer', 'info_panel', 'welcome_screen',
        'landing_page_groups_section', 'landing_page_faq_section'
    ];

    if (!isset($data['advert_name']) || empty(trim($data['advert_name']))) {
        $result['error_variables'][] = ['advert_name'];
        $noerror = false;
    }

    if (!isset($data['advert_max_height']) || empty(trim($data['advert_max_height']))) {
        $result['error_variables'][] = ['advert_max_height'];
        $noerror = false;
    }

    if (!isset($data['advert_placement']) || empty(trim($data['advert_placement'])) || !in_array($data['advert_placement'], $advert_placements)) {
        $result['error_variables'][] = ['advert_placement'];
        $noerror = false;
    }


    if (isset($data['site_advert_id'])) {
        $advert_id = filter_var($data["site_advert_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if ($noerror && !empty($advert_id)) {
        $data['advert_name'] = htmlspecialchars($data['advert_name'], ENT_QUOTES, 'UTF-8');
        $data['advert_min_height'] = filter_var($data['advert_min_height'], FILTER_SANITIZE_NUMBER_INT);
        $data['advert_max_height'] = filter_var($data['advert_max_height'], FILTER_SANITIZE_NUMBER_INT);

        if (isset($data['disabled']) && $data['disabled'] === 'yes') {
            $disabled = 1;
        }

        DB::connect()->update("site_advertisements", [
            "site_advert_name" => $data['advert_name'],
            "site_advert_min_height" => $data['advert_min_height'],
            "site_advert_max_height" => $data['advert_max_height'],
            "site_advert_placement" => $data['advert_placement'],
            "site_advert_content" => $data['advert_content'],
            "disabled" => $disabled,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ], ["site_advert_id" => $advert_id]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'site_adverts';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }

    }
}
?>