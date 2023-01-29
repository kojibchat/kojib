<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$audio_content_ids = array();

if (role(['permissions' => ['audio_player' => 'delete']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    if (isset($data['audio_content_id'])) {
        if (!is_array($data['audio_content_id'])) {
            $audio_content_ids[] = $data["audio_content_id"];
        } else {
            $audio_content_ids = array_filter($data["audio_content_id"], 'ctype_digit');
        }
    }

    if (!empty($audio_content_ids)) {

        foreach ($audio_content_ids as $audio_content_id) {
            $audio_content_id = filter_var($audio_content_id, FILTER_SANITIZE_NUMBER_INT);

            if (!empty($audio_content_id)) {

                if (isset($data['audio_file'])) {

                    $data['audio_file'] = sanitize_filename($data['audio_file']);

                    if (!empty($data['audio_file'])) {
                        $audio_content_id = 'assets/files/audio_player/playlists/'.$audio_content_id.'/'.$data['audio_file'];
                        files('delete', ['delete' => $audio_content_id, 'real_path' => true]);
                    }
                } else {

                    DB::connect()->delete("audio_player", ["audio_content_id" => $audio_content_ids]);

                    if (!DB::connect()->error) {

                        $old_file = "assets/files/audio_player/images/".$audio_content_id.Registry::load('config')->file_seperator."*.*";
                        foreach (glob($old_file) as $oldimage) {
                            unlink($oldimage);
                        }

                        $audio_content_id = 'assets/files/audio_player/playlists/'.$audio_content_id;
                        files('delete', ['delete' => $audio_content_id, 'real_path' => true]);
                    }
                }
            }
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = ['audio_player_contents', 'playlist'];
    } else {
        $result['error_message'] = Registry::load('strings')->went_wrong;
    }
}
?>