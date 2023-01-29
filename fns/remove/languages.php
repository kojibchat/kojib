<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$language_ids = array();
$irremovable = [1];
$irremovable[] = [Registry::load('settings')->default_language];

if (role(['permissions' => ['languages' => 'delete']])) {

    if (isset($data['language_id'])) {
        if (!is_array($data['language_id'])) {
            $data["language_id"] = filter_var($data["language_id"], FILTER_SANITIZE_NUMBER_INT);
            $language_ids[] = $data["language_id"];
        } else {
            $language_ids = array_filter($data["language_id"], 'ctype_digit');
        }

        if (($key = array_search('1', $language_ids)) !== false) {
            unset($language_ids[$key]);
        }

        if (($key = array_search(Registry::load('settings')->default_language, $language_ids)) !== false) {
            unset($language_ids[$key]);
        }
    }

    if (!empty($language_ids)) {
        DB::connect()->delete("language_strings", ["language_id" => $language_ids]);
        DB::connect()->delete("languages", ["language_id" => $language_ids]);

        if (!DB::connect()->error) {

            DB::connect()->update("site_users_settings", ["language_id" => 0], ["language_id" => $language_ids]);

            foreach ($language_ids as $language_id) {
                foreach (glob("assets/files/languages/".$language_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                    unlink($oldimage);
                }
            }

            cache(['rebuild' => 'languages']);

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = ['languages', 'language_strings'];

        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}
?>