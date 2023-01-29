<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$avatars = array();

if (role(['permissions' => ['avatars' => 'delete']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    if (isset($data['avatar'])) {
        if (!is_array($data['avatar'])) {
            $avatars[] = $data["avatar"];
        } else {
            $avatars = $data["avatar"];
        }
    }

    if (!empty($avatars)) {

        foreach ($avatars as $avatar) {

            $avatar = sanitize_filename($avatar);

            if (!empty($avatar)) {
                $location = 'assets/files/avatars/'.$avatar;
                files('delete', ['delete' => $location, 'real_path' => true]);
            }

        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = 'avatars';

    }
}
?>