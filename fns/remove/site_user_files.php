<?php

include 'fns/filters/load.php';
include 'fns/files/load.php';

$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$user_id = Registry::load('current_user')->id;

if (role(['permissions' => ['storage' => 'delete_files']])) {

    if (isset($data['user_id'])) {
        if (role(['permissions' => ['storage' => 'super_privileges']])) {
            if (!is_array($data['user_id'])) {
                $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
                $user_id = array();
                $user_id[] = $data["user_id"];
            } else {
                $user_id = array_filter($data["user_id"], 'ctype_digit');
            }
        }
    }

    if (!empty($user_id)) {

        if (isset($data['file'])) {

            if (is_array($user_id)) {
                $user_id = $user_id[0];
            }

            if (is_array($data['file'])) {
                $files = $data['file'];
            } else {
                $files = array();
                $files[] = $data['file'];
            }

            foreach ($files as $file) {
                $file_name = sanitize_filename($file);

                if (!empty($file_name)) {
                    $file = 'assets/files/storage/'.$user_id.'/files/'.$file_name;
                    files('delete', ['delete' => $file, 'real_path' => true]);

                    $file = 'assets/files/storage/'.$user_id.'/thumbnails/'.$file_name;
                    files('delete', ['delete' => $file, 'real_path' => true]);

                }
            }
        } else if (isset($data['delete_all']) && $data['delete_all']) {

            $user_ids = array();

            if (!is_array($user_id)) {
                $user_ids[] = $user_id;
            } else {
                $user_ids = $user_id;
            }

            foreach ($user_ids as $user_id) {

                $file = 'assets/files/storage/'.$user_id.'/';
                files('delete', ['delete' => $file, 'real_path' => true]);
            }
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = ['site_user_files', 'storage'];
    }
}
?>