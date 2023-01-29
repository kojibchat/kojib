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

    $status = ["enable", "disable"];

    if (!isset($data['status']) || empty($data['status'])) {
        $result['error_variables'][] = ['status'];
        $noerror = false;
    } else if (!in_array($data['status'], $status)) {
        $result['error_variables'][] = ['status'];
        $noerror = false;
    }

    $required_fields = [
        'hero_section_heading', 'hero_section_description',
        'groups_section_heading', 'groups_section_description',
        'footer_text', 'footer_block_one_heading', 'footer_block_one_description',
        'footer_block_two_heading', 'footer_block_two_description', 'copyright_notice',
        'faq_section_heading'
    ];

    foreach ($required_fields as $required_field) {

        if (!isset($data[$required_field]) || empty($data[$required_field])) {
            $result['error_variables'][] = [$required_field];
            $noerror = false;
        }
    }

    if ($noerror) {

        $language_id = Registry::load('current_user')->language;

        if (isset($data["language_id"])) {
            $data["language_id"] = filter_var($data["language_id"], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($data["language_id"])) {
                $language_id = $data["language_id"];
            }
        }

        language(['edit_string' => 'landing_page_hero_section_heading', 'value' => $data['hero_section_heading'], 'language_id' => $language_id]);
        language(['edit_string' => 'landing_page_hero_section_description', 'value' => $data['hero_section_description'], 'language_id' => $language_id]);
        language(['edit_string' => 'landing_page_groups_section_heading', 'value' => $data['groups_section_heading'], 'language_id' => $language_id]);
        language(['edit_string' => 'landing_page_groups_section_description', 'value' => $data['groups_section_description'], 'language_id' => $language_id]);
        language(['edit_string' => 'landing_page_footer_text', 'value' => $data['footer_text'], 'language_id' => $language_id]);
        language(['edit_string' => 'landing_page_footer_block_one_heading', 'value' => $data['footer_block_one_heading'], 'language_id' => $language_id]);
        language(['edit_string' => 'landing_page_footer_block_one_description', 'value' => $data['footer_block_one_description'], 'language_id' => $language_id]);
        language(['edit_string' => 'landing_page_footer_block_two_heading', 'value' => $data['footer_block_two_heading'], 'language_id' => $language_id]);
        language(['edit_string' => 'landing_page_footer_block_two_description', 'value' => $data['footer_block_two_description'], 'language_id' => $language_id]);
        language(['edit_string' => 'landing_page_copyright_notice', 'value' => $data['copyright_notice'], 'language_id' => $language_id]);
        language(['edit_string' => 'landing_page_faq_section_heading', 'value' => $data['faq_section_heading'], 'language_id' => $language_id]);


        for ($index = 1; $index <= 10; $index++) {
            $question_index = 'faq_question_'.$index;
            $answer_index = $question_index.'_answer';

            if (!isset($data[$question_index])) {
                $data[$question_index] = '';
            }

            if (!isset($data[$answer_index])) {
                $data[$answer_index] = '';
            }

            language(['edit_string' => 'landing_page_'.$question_index, 'value' => $data[$question_index], 'language_id' => $language_id]);
            language(['edit_string' => 'landing_page_'.$answer_index, 'value' => $data[$answer_index], 'language_id' => $language_id]);

        }


        if (isset($_FILES['hero_section_image']['name']) && !empty($_FILES['hero_section_image']['name'])) {
            if (isImage($_FILES['hero_section_image']['tmp_name'])) {

                $landing_page_hero_image = 'assets/files/defaults/landing_page_hero_image.jpg';

                if (file_exists($landing_page_hero_image)) {
                    unlink($landing_page_hero_image);
                }

                if (files('upload', ['upload' => 'hero_section_image', 'folder' => 'defaults', 'saveas' => 'landing_page_hero_image.jpg'])['result']) {
                    files('resize_img', ['resize' => 'defaults/landing_page_hero_image.jpg', 'width' => 1920, 'height' => 1080, 'crop' => false]);
                }
            }
        }

        if (isset($data['facebook_url']) && $data['facebook_url'] !== Registry::load('settings')->facebook_url) {
            $data['facebook_url'] = htmlspecialchars(trim($data['facebook_url']), ENT_QUOTES, 'UTF-8');
            DB::connect()->update("settings",
                ["value" => $data['facebook_url'], "updated_on" => Registry::load('current_user')->time_stamp],
                ["setting" => 'facebook_url']
            );
        }

        if (isset($data['instagram_url']) && $data['instagram_url'] !== Registry::load('settings')->instagram_url) {
            $data['instagram_url'] = htmlspecialchars(trim($data['instagram_url']), ENT_QUOTES, 'UTF-8');
            DB::connect()->update("settings",
                ["value" => $data['instagram_url'], "updated_on" => Registry::load('current_user')->time_stamp],
                ["setting" => 'instagram_url']
            );
        }

        if (isset($data['twitter_url']) && $data['twitter_url'] !== Registry::load('settings')->twitter_url) {
            $data['twitter_url'] = htmlspecialchars(trim($data['twitter_url']), ENT_QUOTES, 'UTF-8');
            DB::connect()->update("settings",
                ["value" => $data['twitter_url'], "updated_on" => Registry::load('current_user')->time_stamp],
                ["setting" => 'twitter_url']
            );
        }

        if (isset($data['linkedin_url']) && $data['linkedin_url'] !== Registry::load('settings')->linkedin_url) {
            $data['linkedin_url'] = htmlspecialchars(trim($data['linkedin_url']), ENT_QUOTES, 'UTF-8');
            DB::connect()->update("settings",
                ["value" => $data['linkedin_url'], "updated_on" => Registry::load('current_user')->time_stamp],
                ["setting" => 'linkedin_url']
            );
        }

        if (isset($data['twitch_url']) && $data['twitch_url'] !== Registry::load('settings')->twitch_url) {
            $data['twitch_url'] = htmlspecialchars(trim($data['twitch_url']), ENT_QUOTES, 'UTF-8');
            DB::connect()->update("settings",
                ["value" => $data['twitch_url'], "updated_on" => Registry::load('current_user')->time_stamp],
                ["setting" => 'twitch_url']
            );
        }


        if (isset($data['groups_section_status']) && $data['groups_section_status'] !== Registry::load('settings')->groups_section_status) {

            if (!in_array($data['groups_section_status'], $status)) {
                $data['groups_section_status'] = 'enable';
            }

            DB::connect()->update("settings",
                ["value" => $data['groups_section_status'], "updated_on" => Registry::load('current_user')->time_stamp],
                ["setting" => 'groups_section_status']
            );
        }

        if (isset($data['faq_section_status']) && $data['faq_section_status'] !== Registry::load('settings')->faq_section_status) {

            if (!in_array($data['faq_section_status'], $status)) {
                $data['faq_section_status'] = 'enable';
            }

            DB::connect()->update("settings",
                ["value" => $data['faq_section_status'], "updated_on" => Registry::load('current_user')->time_stamp],
                ["setting" => 'faq_section_status']
            );
        }


        if ($data['status'] !== Registry::load('settings')->landing_page) {
            DB::connect()->update("settings",
                ["value" => $data['status'], "updated_on" => Registry::load('current_user')->time_stamp],
                ["setting" => 'landing_page']);
        }

        if ($data['hero_section_animation'] !== Registry::load('settings')->hero_section_animation) {
            DB::connect()->update("settings",
                ["value" => $data['hero_section_animation'], "updated_on" => Registry::load('current_user')->time_stamp],
                ["setting" => 'hero_section_animation']);
        }

        cache(['rebuild' => 'settings']);

        $result['success'] = true;
        $result['todo'] = 'refresh';
    }
}