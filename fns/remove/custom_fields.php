<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$field_ids = array();
$irremovable = [1, 2, 3, 4, 5, 6];

if (role(['permissions' => ['custom_fields' => 'delete']])) {

    if (isset($data['field_id'])) {
        if (!is_array($data['field_id'])) {
            $data["field_id"] = filter_var($data["field_id"], FILTER_SANITIZE_NUMBER_INT);
            $field_ids[] = $data["field_id"];
        } else {
            $field_ids = array_filter($data["field_id"], 'ctype_digit');
        }
    }

    $field_ids = array_diff($field_ids, $irremovable);

    if (!empty($field_ids)) {

        DB::connect()->delete("custom_fields", ["field_id" => $field_ids]);

        if (!DB::connect()->error) {

            foreach ($field_ids as $field_id) {
                $custom_field_string[] = 'custom_field_'.$field_id;
                $custom_field_options[] = 'custom_field_'.$field_id.'_options';
            }

            language(['delete_string' => $custom_field_string]);
            language(['delete_string' => $custom_field_options]);

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'custom_fields';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}
?>