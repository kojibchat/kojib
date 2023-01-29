<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$field_value_ids = array();

if (role(['permissions' => ['custom_fields' => 'delete']])) {
    if (isset($data['field_value_id'])) {
        if (!is_array($data['field_value_id'])) {
            $data["field_value_id"] = filter_var($data["field_value_id"], FILTER_SANITIZE_NUMBER_INT);
            $field_value_ids[] = $data["field_value_id"];
        } else {
            $field_value_ids = array_filter($data["field_value_id"], 'ctype_digit');
        }
    }

    if (!empty($field_value_ids)) {

        DB::connect()->delete("custom_fields_values", ["field_value_id" => $field_value_ids]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'custom_fields_values';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}
?>