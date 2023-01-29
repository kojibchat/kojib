<?php

if (role(['permissions' => ['audio_player' => 'listen_music']])) {

    $audio_content_id = 0;
    $columns = [
        'audio_player.audio_content_id', 'audio_player.audio_title',
        'audio_player.audio_description', 'audio_player.audio_type',
        'audio_player.radio_stream_url',
    ];

    $where["audio_player.disabled[!]"] = 1;

    if (isset($data['audio_content_id'])) {
        $audio_content_id = filter_var($data["audio_content_id"], FILTER_SANITIZE_NUMBER_INT);
        if (!empty($audio_content_id)) {
            $where["audio_player.audio_content_id"] = $audio_content_id;
        }
    }

    $audio_records = DB::connect()->select('audio_player', $columns, $where);

    if (!empty($audio_content_id) && !isset($audio_records[0])) {
        return false;
    }

    $i = 1;
    $output = array();

    if (empty($audio_content_id)) {

        $output['loaded'] = new stdClass();
        $output['loaded']->title = Registry::load('strings')->audio_player;
        $output['loaded']->button['text'] = Registry::load('strings')->refresh;
        $output['loaded']->button_attributes['class'] = 'load_audio_player';
        $output['loaded']->button_attributes['refresh'] = true;

        foreach ($audio_records as $audio_record) {

            $output['content'][$i] = new stdClass();
            $output['content'][$i]->image = get_image(['from' => 'audio_player/images', 'search' => $audio_record['audio_content_id']]);
            $output['content'][$i]->title = $audio_record['audio_title'];
            $output['content'][$i]->class = "audio_record";

            if ((int)$audio_record['audio_type'] === 1) {
                $output['content'][$i]->class = "audio_record playable";
                $output['content'][$i]->option['text'] = Registry::load('strings')->play;
                $output['content'][$i]->option['class'] = 'load_audio';
                $output['content'][$i]->option_attributes['audio_type'] = 'radio_station';
                $output['content'][$i]->option_attributes['audio_url'] = $audio_record['radio_stream_url'];
                $output['content'][$i]->option_attributes['audio_content_id'] = $audio_record['audio_content_id'];
                $output['content'][$i]->subtitle = Registry::load('strings')->radio_station;
                $output['content'][$i]->description = $audio_record['audio_description'];
            } else {
                $output['content'][$i]->option['text'] = Registry::load('strings')->view;
                $output['content'][$i]->option['class'] = 'load_audio_player';
                $output['content'][$i]->option_attributes['audio_content_id'] = $audio_record['audio_content_id'];
                $output['content'][$i]->subtitle = Registry::load('strings')->playlist;
            }


            $i++;
        }
    } else {

        $output['loaded'] = new stdClass();
        $output['loaded']->title = $audio_records[0]['audio_title'];
        $output['loaded']->button['text'] = Registry::load('strings')->go_back;
        $output['loaded']->button_attributes['class'] = 'load_audio_player';
        $output['loaded']->button_attributes['refresh'] = true;

        $location = 'assets/files/audio_player/playlists/'.$audio_content_id.'/*';
        $extensions = rangeof_chars('wav,mp3,webm,ogg,mp4');
        $location = $location.'.{'.$extensions.'}';

        $audio_files = glob($location, GLOB_BRACE);

        foreach ($audio_files as $audio_file) {

            $file_name = basename($audio_file);

            $output['content'][$i] = new stdClass();
            $output['content'][$i]->title = explode('-gr-', $file_name, 2);

            if (isset($output['content'][$i]->title[1])) {
                $output['content'][$i]->title = $output['content'][$i]->title[1];
            } else {
                $output['content'][$i]->title = $file_name;
            }


            $output['content'][$i]->title = pathinfo($output['content'][$i]->title);
            $output['content'][$i]->title = $output['content'][$i]->title['filename'];
            $output['content'][$i]->subtitle = $audio_records[0]['audio_title'];
            $output['content'][$i]->description = $audio_records[0]['audio_description'];
            $output['content'][$i]->image = get_image(['from' => 'audio_player/images', 'search' => $audio_content_id]);
            $output['content'][$i]->class = "audio_record playable";

            $output['content'][$i]->option['text'] = Registry::load('strings')->play;
            $output['content'][$i]->option['class'] = 'load_audio';
            $output['content'][$i]->option_attributes['audio_type'] = 'audio_file';
            $output['content'][$i]->option_attributes['audio_file_name'] = $file_name;
            $output['content'][$i]->option_attributes['audio_content_id'] = $audio_content_id;
            $output['content'][$i]->option_attributes['mime_type'] = mime_content_type($audio_file);
            $output['content'][$i]->option_attributes['audio_url'] = Registry::load('config')->site_url.$audio_file;

            $i++;
        }
    }
}
?>