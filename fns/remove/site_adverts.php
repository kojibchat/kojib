<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$advert_ids = array();

if (role(['permissions' => ['site_adverts' => 'delete']])) {
    if (isset($data['site_advert_id'])) {
        if (!is_array($data['site_advert_id'])) {
            $data["site_advert_id"] = filter_var($data["site_advert_id"], FILTER_SANITIZE_NUMBER_INT);
            $advert_ids[] = $data["site_advert_id"];
        } else {
            $advert_ids = array_filter($data["site_advert_id"], 'ctype_digit');
        }
    }

    if (!empty($advert_ids)) {

        DB::connect()->delete("site_advertisements", ["site_advert_id" => $advert_ids]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'site_adverts';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
        }
    }
}
?>