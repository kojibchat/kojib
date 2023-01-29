<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['audio_player' => 'edit']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    $noerror = true;
    $disabled = 0;

    if (!isset($data['audio_title']) || empty($data['audio_title'])) {
        $result['error_variables'][] = ['audio_title'];
        $noerror = false;
    }
    if (!isset($data['description']) || empty($data['description'])) {
        $result['error_variables'][] = ['description'];
        $noerror = false;
    }
    if (isset($data['audio_type']) && $data['audio_type'] === 'radio_station') {
        if (!isset($data['stream_url']) || empty($data['stream_url'])) {
            $result['error_variables'][] = ['stream_url'];
            $noerror = false;
        } else if (filter_var(trim(base64_decode($data['stream_url'])), FILTER_VALIDATE_URL) === FALSE) {
            $result['error_variables'][] = ['stream_url'];
            $noerror = false;
        }
    }


    if (isset($data['audio_content_id'])) {
        $audio_content_id = filter_var($data["audio_content_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if ($noerror && !empty($audio_content_id)) {
        $data['audio_title'] = htmlspecialchars($data['audio_title'], ENT_QUOTES, 'UTF-8');
        $data['description'] = htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8');

        if (isset($data['audio_type']) && $data['audio_type'] === 'radio_station') {
            $audio_type = 1;
            $data['stream_url'] = trim(htmlspecialchars(base64_decode($data['stream_url']), ENT_QUOTES, 'UTF-8'));
            $radio_stream_url = $data['stream_url'];
        } else {
            $audio_type = 2;
            $radio_stream_url = '';
        }

        if (isset($data['disabled']) && $data['disabled'] === 'yes') {
            $disabled = 1;
        }

        DB::connect()->update("audio_player", [
            "audio_title" => $data['audio_title'],
            "audio_description" => $data['description'],
            "audio_type" => $audio_type,
            "radio_stream_url" => $radio_stream_url,
            "disabled" => $disabled,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ], ["audio_content_id" => $audio_content_id]);

        if (!DB::connect()->error) {

            $playlist_folder = 'assets/files/audio_player/playlists/'.$audio_content_id;

            if (!file_exists($playlist_folder)) {
                mkdir($playlist_folder, 0755, true);
            }

            if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
                if (isImage($_FILES['image']['tmp_name'])) {

                    $old_file = "assets/files/audio_player/images/".$audio_content_id.Registry::load('config')->file_seperator."*.*";
                    foreach (glob($old_file) as $oldimage) {
                        unlink($oldimage);
                    }

                    $extension = pathinfo($_FILES['image']['name'])['extension'];
                    $filename = $audio_content_id.Registry::load('config')->file_seperator.random_string(['length' => 6]).'.'.$extension;

                    if (files('upload', ['upload' => 'image', 'folder' => 'audio_player/images', 'saveas' => $filename])['result']) {
                        files('resize_img', ['resize' => 'audio_player/images/'.$filename, 'width' => 150, 'height' => 150, 'crop' => true]);
                    }

                }
            }

            if ((int)$audio_type === 2) {

                if (isset($_FILES['audio_files']['name']) && !empty($_FILES['audio_files']['name'])) {

                    $upload_info = [
                        'upload' => 'audio_files',
                        'folder' => $playlist_folder,
                        'prepend_random_string' => true,
                        'sanitize_filename' => true,
                        'real_path' => true,
                        'multi_upload' => true,
                        'only_allow' => ['audio/wav', 'audio/mpeg', 'video/mp4', 'audio/mp4', 'audio/webm', 'audio/ogg', 'audio/x-wav']
                    ];

                    files('upload', $upload_info);
                }
            }

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = ['audio_player_contents', 'playlist'];
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }

    }
}

?>