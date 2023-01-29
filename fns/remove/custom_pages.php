<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$page_ids = array();

if (role(['permissions' => ['custom_pages' => 'delete']])) {
    if (isset($data['page_id'])) {
        if (!is_array($data['page_id'])) {
            $data["page_id"] = filter_var($data["page_id"], FILTER_SANITIZE_NUMBER_INT);
            $page_ids[] = $data["page_id"];
        } else {
            $page_ids = array_filter($data["page_id"], 'ctype_digit');
        }
    }

    if (isset($data['page_id']) && !empty($data['page_id'])) {

        DB::connect()->delete("custom_pages", ["page_id" => $page_ids]);

        if (!DB::connect()->error) {

            foreach ($page_ids as $page_id) {
                foreach (glob("assets/files/custom_pages/".$page_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                    unlink($oldimage);
                }
                $custom_page_string[] = 'custom_page_'.$page_id;
                $custom_page_content[] = 'custom_page_'.$page_id.'_content';
            }

            language(['delete_string' => $custom_page_string]);
            language(['delete_string' => $custom_page_content]);

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
?>