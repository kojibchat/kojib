<?php

$update = null;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

if (isset($data['user_id'])) {

    $data['user_id'] = filter_var($data['user_id'], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data['user_id'])) {

        if (!empty($update)) {
            $where = [
                'login_sessions.user_id' => $data['user_id']
            ];

            DB::connect()->update('login_sessions', $update, $where);

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'site_users';

            if (isset($data['info_box'])) {
                $result['info_box']['user_id'] = $data['user_id'];
            }

        }
    }
}
?>