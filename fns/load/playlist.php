<?php

if (role(['permissions' => ['audio_player' => 'view']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    $i = 1;
    $audio_content_id = 0;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->playlist;

    if (isset($data['audio_content_id'])) {
        $audio_content_id = filter_var($data["audio_content_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if (!empty($audio_content_id)) {

        $columns = [
            'audio_player.audio_title', 'audio_player.disabled',
            'audio_player.audio_type'
        ];

        $where["audio_player.audio_content_id"] = $audio_content_id;
        $where["LIMIT"] = 1;

        $playlist = DB::connect()->select('audio_player', $columns, $where);

        if (!isset($playlist[0]) || (int)$playlist[0]['audio_type'] !== 2) {
            return false;
        }

        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }

        $output['loaded']->title = $playlist[0]['audio_title'];
        $output['loaded']->offset = intval($data["offset"])+intval(Registry::load('settings')->records_per_call);

        if (role(['permissions' => ['audio_player' => 'edit']])) {
            $output['todo'] = new stdClass();
            $output['todo']->class = 'load_form';
            $output['todo']->title = Registry::load('strings')->add_audio_files;
            $output['todo']->attributes['form'] = 'audio_player_contents';
            $output['todo']->attributes['data-audio_content_id'] = $audio_content_id;
        }

        $location = 'assets/files/audio_player/playlists/'.$audio_content_id.'/*';

        if (!empty($data["search"])) {
            $data['search'] = rangeof_chars(sanitize_filename($data['search']));
            $location = $location.$data['search'].'*';
        }

        $extensions = rangeof_chars('wav,mp3,webm,ogg,mp4');
        $location = $location.'.{'.$extensions.'}';

        $audio_files = glob($location, GLOB_BRACE);
        $audio_files = array_slice($audio_files, $data["offset"], Registry::load('settings')->records_per_call);

        foreach ($audio_files as $audio_file) {

            $file_name = basename($audio_file);

            $total_stickers = 0;
            $output['content'][$i] = new stdClass();
            $output['content'][$i]->class = "audio_file";
            $output['content'][$i]->identifier = $audio_content_id;
            $output['content'][$i]->image = get_image(['from' => 'audio_player/images', 'search' => $audio_content_id]);

            $output['content'][$i]->button['text'] = Registry::load('strings')->play;
            $output['content'][$i]->button['class'] = 'play_audio_file';

            $output['content'][$i]->title = explode('-gr-', $file_name, 2);

            if (isset($output['content'][$i]->title[1])) {
                $output['content'][$i]->title = $output['content'][$i]->title[1];
            } else {
                $output['content'][$i]->title = $file_name;
            }


            $output['content'][$i]->title = pathinfo($output['content'][$i]->title);
            $output['content'][$i]->title = $output['content'][$i]->title['filename'];

            $output['content'][$i]->subtitle = files('getsize', ['getsize_of' => $audio_file, 'real_path' => true]);

            $output['content'][$i]->icon = 0;
            $output['content'][$i]->unread = 0;

            if (role(['permissions' => ['audio_player' => 'edit']])) {
                $output['options'][$i][2] = new stdClass();
                $output['options'][$i][2]->option = Registry::load('strings')->rename;
                $output['options'][$i][2]->class = 'load_form';
                $output['options'][$i][2]->attributes['form'] = 'audio_file';
                $output['options'][$i][2]->attributes['data-audio_content_id'] = $audio_content_id;
                $output['options'][$i][2]->attributes['data-audio_file'] = $file_name;
            }

            if (role(['permissions' => ['audio_player' => 'delete']])) {
                $output['options'][$i][3] = new stdClass();
                $output['options'][$i][3]->option = Registry::load('strings')->delete;
                $output['options'][$i][3]->class = 'ask_confirmation';
                $output['options'][$i][3]->attributes['data-remove'] = 'audio_player_contents';
                $output['options'][$i][3]->attributes['data-audio_content_id'] = $audio_content_id;
                $output['options'][$i][3]->attributes['data-audio_file'] = $file_name;
                $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
                $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            }

            $i++;
        }
    }
}
?>