<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['languages' => 'edit']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    $string_id = 0;
    $noerror = true;

    if (!isset($data['string_value']) || empty($data['string_value'])) {
        $result['error_variables'][] = ['string_value'];
        $noerror = false;
    }

    if (isset($data['string_id'])) {
        $string_id = filter_var($data["string_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if ($noerror && !empty($string_id)) {

        $data['string_value'] = htmlspecialchars($data['string_value'], ENT_QUOTES, 'UTF-8');

        $where = ["language_strings.string_id" => $string_id];

        $update_data = [
            "language_strings.string_value" => $data['string_value'],
        ];

        DB::connect()->update("language_strings", $update_data, $where);

        if (!DB::connect()->error) {
            cache(['rebuild' => 'languages']);
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'language_strings';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}

?>