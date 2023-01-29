<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$provider_ids = array();

if (role(['permissions' => ['social_login_providers' => 'delete']])) {

    if (isset($data['social_login_provider_id'])) {
        if (!is_array($data['social_login_provider_id'])) {
            $data["social_login_provider_id"] = filter_var($data["social_login_provider_id"], FILTER_SANITIZE_NUMBER_INT);
            $provider_ids[] = $data["social_login_provider_id"];
        } else {
            $provider_ids = array_filter($data["social_login_provider_id"], 'ctype_digit');
        }
    }

    if (!empty($provider_ids)) {

        DB::connect()->delete("social_login_providers", ["social_login_provider_id" => $provider_ids]);

        if (!DB::connect()->error) {

            foreach ($provider_ids as $provider_id) {
                foreach (glob("assets/files/social_login/".$provider_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                    unlink($oldimage);
                }
            }

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'social_login_providers';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}
?>