<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['custom_pages' => 'edit']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    $noerror = true;
    $meta_title = $meta_description = '';
    $disabled = 0;
    $clean_html = false;

    $language_id = Registry::load('current_user')->language;

    if (isset($data["language_id"])) {
        $data["language_id"] = filter_var($data["language_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data["language_id"])) {
            $language_id = $data["language_id"];
        }
    }

    if (isset($data['page_id'])) {

        $page_id = filter_var($data["page_id"], FILTER_SANITIZE_NUMBER_INT);
        $columns = $join = $where = null;
        $columns = ['language_strings.string_value(page_title)', 'custom_pages.slug'];

        $where["custom_pages.page_id"] = $page_id;
        $where["LIMIT"] = 1;

        $join["[>]language_strings"] = ["custom_pages.string_constant" => "string_constant", "AND" => ["language_id" => $language_id]];

        $custompage = DB::connect()->select('custom_pages', $join, $columns, $where);

        if (!isset($custompage[0])) {
            return false;
        } else {
            $custompage = $custompage[0];
        }

        $columns = $join = $where = null;
        $columns = ['language_strings.string_value(page_content)'];
        $where["language_strings.language_id"] = $language_id;
        $where["language_strings.string_constant"] = 'custom_page_'.$data["page_id"].'_content';
        $where["LIMIT"] = 1;

        $page_content = DB::connect()->select('language_strings', $columns, $where);


        if (!isset($data['page_title']) || empty($data['page_title'])) {
            $result['error_variables'][] = ['page_title'];
            $noerror = false;
        }

        if (!isset($data['slug']) || empty(sanitize_slug($data['slug']))) {
            $result['error_variables'][] = ['slug'];
            $noerror = false;
        } else {
            $data['slug'] = sanitize_slug($data['slug']);
            if (slug_exists($data['slug']) && $custompage['slug'] !== $data['slug']) {
                $result['error_message'] = Registry::load('strings')->slug_already_exists;
                $result['error_key'] = 'slug_already_exists';
                $result['error_variables'][] = ['slug'];
                $noerror = false;
            }
        }

        if ($noerror && !empty($page_id)) {
            $data['page_title'] = htmlspecialchars($data['page_title'], ENT_QUOTES, 'UTF-8');

            if (isset($data['meta_title']) && !empty($data['meta_title'])) {
                $data['meta_title'] = htmlspecialchars(trim($data['meta_title']), ENT_QUOTES, 'UTF-8');
                if (!empty($data['meta_title'])) {
                    $meta_title = $data['meta_title'];
                }
            }


            if (isset($data['meta_description']) && !empty($data['meta_description'])) {
                $data['meta_description'] = htmlspecialchars(trim($data['meta_description']), ENT_QUOTES, 'UTF-8');
                if (!empty($data['meta_description'])) {
                    $meta_description = $data['meta_description'];
                }
            }


            if (isset($data['page_content']) && !empty($data['page_content'])) {
                if ($clean_html) {
                    include('fns/HTMLPurifier/load.php');
                    class HTMLPurifier_Strategy_Null extends HTMLPurifier_Strategy {
                        public function execute($tokens, $config, $context) {
                            return $tokens;
                        }
                    }

                    class HTMLLinter extends HTMLPurifier {
                        public function __construct($config = null) {
                            parent::__construct($config);
                            $this->strategy = new HTMLPurifier_Strategy_Null();
                        }
                    }

                    $linter = new HTMLLinter();
                    $data['page_content'] = $linter->purify($data['page_content']);
                }
            } else {
                $data['page_content'] = '';
            }

            if (isset($data['disabled']) && $data['disabled'] === 'yes') {
                $disabled = 1;
            }

            if (isset($data['who_all_can_view_page'])) {
                $data['who_all_can_view_page'] = array_filter($data['who_all_can_view_page'], 'is_numeric');
                $data["who_all_can_view_page"] = json_encode($data['who_all_can_view_page']);
            } else {
                $data["who_all_can_view_page"] = '';
            }



            $string_constant = 'custom_page_'.$page_id;
            $content_string_constant = $string_constant.'_content';

            DB::connect()->update("custom_pages", [
                "string_constant" => $string_constant,
                "slug" => $data['slug'],
                "meta_title" => $meta_title,
                "meta_description" => $meta_description,
                "disabled" => $disabled,
                "who_all_can_view_page" => $data["who_all_can_view_page"],
                "updated_on" => Registry::load('current_user')->time_stamp,
            ], ["page_id" => $page_id]);

            if (!DB::connect()->error) {

                if (isset($custompage['page_title']) && !empty($custompage['page_title'])) {
                    language(['edit_string' => $string_constant, 'value' => $data['page_title'], 'language_id' => $language_id, 'skip_cache' => true]);
                } else {
                    language(['add_string' => $string_constant, 'value' => $data['page_title'], 'skip_cache' => true]);
                }

                if (isset($page_content[0])) {
                    language(['edit_string' => $content_string_constant, 'value' => $data['page_content'], 'language_id' => $language_id, 'skip_cache' => true]);
                } else {
                    language(['add_string' => $content_string_constant, 'value' => $data['page_content'], 'skip_cache' => true]);
                }

                if (isset($_FILES['featured_image']['name']) && !empty($_FILES['featured_image']['name'])) {
                    if (isImage($_FILES['featured_image']['tmp_name'])) {

                        foreach (glob("assets/files/custom_pages/".$page_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                            unlink($oldimage);
                        }

                        $extension = pathinfo($_FILES['featured_image']['name'])['extension'];
                        $filename = $page_id.Registry::load('config')->file_seperator.random_string(['length' => 6]).'.'.$extension;

                        if (files('upload', ['upload' => 'featured_image', 'folder' => 'custom_pages/', 'saveas' => $filename])['result']) {
                            files('resize_img', ['resize' => 'custom_pages/'.$filename, 'width' => 1920, 'height' => 1000, 'crop' => false]);
                        }
                    }
                }

                $result = array();
                $result['success'] = true;
                $result['todo'] = 'reload';
                $result['reload'] = 'custom_pages';
            } else {
                $result['error_message'] = Registry::load('strings')->went_wrong;
                $result['error_key'] = 'something_went_wrong';
            }

        }
    }
}
?>