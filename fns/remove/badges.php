<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$badge_ids = $string_constants = array();

if (role(['permissions' => ['badges' => 'delete']])) {

    if (isset($data['badge_id'])) {
        if (!is_array($data['badge_id'])) {
            $data["badge_id"] = filter_var($data["badge_id"], FILTER_SANITIZE_NUMBER_INT);
            $badge_ids[] = $data["badge_id"];
        } else {
            $badge_ids = array_filter($data["badge_id"], 'ctype_digit');
        }
    }

    if (!empty($badge_ids)) {

        DB::connect()->delete("badges", ["badge_id" => $badge_ids]);

        if (!DB::connect()->error) {

            foreach ($badge_ids as $badge_id) {
                $string_constants[] = 'badge_'.$badge_id;
                foreach (glob("assets/files/badges/".$badge_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                    unlink($oldimage);
                }
            }

            language(['delete_string' => $string_constants]);

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'badges';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}
?>