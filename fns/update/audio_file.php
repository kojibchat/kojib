<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['audio_player' => 'edit']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    $noerror = true;
    $disabled = 0;
    $audio_content_id = 0;

    $result['success'] = false;
    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    if (!isset($data['new_file_name']) || empty($data['new_file_name'])) {
        $result['error_variables'][] = ['new_file_name'];
        $noerror = false;
    } else {
        $data['new_file_name'] = sanitize_filename($data['new_file_name']);
        if (empty($data['new_file_name'])) {
            $result['error_variables'][] = ['new_file_name'];
            $noerror = false;
        }
    }

    if (!isset($data['audio_file']) || empty($data['audio_file'])) {
        $result['error_variables'][] = ['audio_file'];
        $noerror = false;
    } else {
        $data['audio_file'] = sanitize_filename($data['audio_file']);
        if (empty($data['audio_file'])) {
            $result['error_variables'][] = ['audio_file'];
            $noerror = false;
        }
    }


    if (isset($data['audio_content_id'])) {
        $audio_content_id = filter_var($data["audio_content_id"], FILTER_SANITIZE_NUMBER_INT);
    }


    if ($noerror && !empty($audio_content_id) && !empty($data['audio_file'])) {


        $playlist_folder = 'assets/files/audio_player/playlists/'.$audio_content_id;
        $old_file_name = $playlist_folder.'/'.$data['audio_file'];

        $extension = pathinfo($old_file_name)['extension'];
        $new_file_name = random_string(['length' => 6]).Registry::load('config')->file_seperator.$data['new_file_name'].'.'.$extension;
        $new_file_name = $playlist_folder.'/'.$new_file_name;

        if (file_exists($old_file_name)) {
            rename($old_file_name, $new_file_name);
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = ['audio_player_contents', 'playlist'];
    }
}

?>