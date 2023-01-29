<?php
$page_id = 0;

if (isset(Registry::load('config')->load_page) && !empty(Registry::load('config')->load_page)) {
    $page_id = Registry::load('config')->load_page;
}

if (!empty($page_id)) {

    $columns = [
        'language_strings.string_value(page_title)', 'custom_pages.slug', 'custom_pages.meta_title',
        'custom_pages.meta_description',
    ];

    $join["[>]language_strings"] = ["custom_pages.string_constant" => "string_constant", "AND" => ["language_id" => Registry::load('current_user')->language]];

    $where["custom_pages.page_id"] = $page_id;
    $where["LIMIT"] = 1;


    $custom_page = DB::connect()->select('custom_pages', $join, $columns, $where);

    if (isset($custom_page[0])) {

        $meta_tags['url'] = Registry::load('config')->site_url.$custom_page[0]['slug'].'/';

        if (isset($custom_page[0]['meta_title']) && !empty($custom_page[0]['meta_title'])) {
            $meta_tags['title'] = $custom_page[0]['meta_title'].' - '.Registry::load('settings')->site_name;
        } else {
            $meta_tags['title'] = $custom_page[0]['page_title'].' - '.Registry::load('settings')->site_name;
        }

        if (isset($custom_page[0]['meta_description']) && !empty($custom_page[0]['meta_description'])) {
            $meta_tags['description'] = $custom_page[0]['meta_description'];
        } else if (isset($custom_page[0]['description']) && !empty($custom_page[0]['description'])) {
            $meta_tags['description'] = $custom_page[0]['description'];
        }

        if (get_image(['from' => 'custom_pages', 'search' => $page_id, 'exists' => true])) {
            $meta_tags['social_share_image'] = get_image(['from' => 'custom_pages', 'search' => $page_id]);
        }

    }
}
?>