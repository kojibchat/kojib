<?php

if (isset($data["page_id"])) {

    $data["page_id"] = filter_var($data["page_id"], FILTER_SANITIZE_NUMBER_INT);
    $page_accessible = true;

    if (!empty($data["page_id"])) {

        $columns = [
            'language_strings.string_value(page_title)', 'custom_pages.disabled',
            'custom_pages.slug', 'custom_pages.meta_title',
            'custom_pages.meta_description', 'custom_pages.who_all_can_view_page'
        ];

        $join["[>]language_strings"] = ["custom_pages.string_constant" => "string_constant", "AND" => ["language_id" => Registry::load('current_user')->language]];

        $where["custom_pages.page_id"] = $data["page_id"];

        if (!role(['permissions' => ['custom_pages' => 'view']])) {
            $where["custom_pages.disabled[!]"] = 1;
        }

        $where["LIMIT"] = 1;

        $page = DB::connect()->select('custom_pages', $join, $columns, $where);

        if (isset($page[0]) && $page[0]['who_all_can_view_page'] !== 'all') {
            $who_all_can_view_page = json_decode($page[0]['who_all_can_view_page']);
            if (!in_array(Registry::load('current_user')->site_role, $who_all_can_view_page)) {
                $page_accessible = false;
            }
        }

        if (isset($page[0]) && $page_accessible) {

            $page = $page[0];


            $columns = $join = $where = null;
            $columns = ['language_strings.string_value(page_content)'];
            $where["language_strings.language_id"] = Registry::load('current_user')->language;
            $where["language_strings.string_constant"] = 'custom_page_'.$data["page_id"].'_content';
            $where["LIMIT"] = 1;

            $page_content = DB::connect()->select('language_strings', $columns, $where);

            if (isset($page_content[0])) {
                $page_content = $page_content[0]['page_content'];
            } else {
                $page_content = '';
            }

            $output = array();
            $output['title'] = $page['page_title'];
            $output['page_content'] = $page_content;

            if (!empty($page['meta_title'])) {
                $output['browser_title'] = $page['meta_title'].' - '.Registry::load('settings')->site_name;
            } else {
                $output['browser_title'] = $page['page_title'].' - '.Registry::load('settings')->site_name;
            }

            if (!empty($page['slug'])) {
                $output['browser_address_bar'] = Registry::load('config')->site_url.$page['slug'].'/';
            }

        } else {
            $output = array();
            $output['title'] = Registry::load('strings')->not_found;
            $output['page_content'] = '';
            $output['page_error'] = 'not_found';

            if (!$page_accessible) {
                $output['title'] = Registry::load('strings')->permission_denied;
                $output['page_content'] = '<center>'.Registry::load('strings')->access_denied_message.'</center>';
            }
        }

    }
}
?>