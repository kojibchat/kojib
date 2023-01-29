<?php

if (role(['permissions' => ['stickers' => 'view']])) {

    include('fns/filters/load.php');

    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->stickers;

    if (role(['permissions' => ['stickers' => 'delete']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'sticker_packs';
        $output['multiple_select']->attributes['multi_select'] = 'sticker_pack';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    if (role(['permissions' => ['stickers' => 'create']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->create_sticker_pack;
        $output['todo']->attributes['form'] = 'sticker_packs';
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['loaded']->offset = intval($data["offset"])+intval(Registry::load('settings')->records_per_call);

    $location = 'assets/files/stickers/*';

    if (!empty($data["search"])) {
        $data['search'] = rangeof_chars(sanitize_filename($data['search']));
        $location = $location.$data['search'].'*';
    }


    $stickers = glob($location);
    $stickers = array_slice($stickers, $data["offset"], Registry::load('settings')->records_per_call);
    $i = 1;

    foreach ($stickers as $sticker) {
        $pack_name = basename($sticker);
        $stickericon = $sticker."/sticker_pack_icon.png";
        $total_stickers = new FilesystemIterator($sticker, FilesystemIterator::SKIP_DOTS);
        $total_stickers = iterator_count($total_stickers);

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->class = "sticker_pack";

        if (file_exists($stickericon)) {
            $total_stickers = $total_stickers-1;
            $output['content'][$i]->image = Registry::load('config')->site_url.$stickericon;
        } else {
            $output['content'][$i]->image = Registry::load('config')->site_url.'assets/files/defaults/stickers.png';
        }

        $output['content'][$i]->title = $pack_name;
        $output['content'][$i]->subtitle = $total_stickers.' '.Registry::load('strings')->stickers;
        $output['content'][$i]->identifier = $pack_name;
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        $output['options'][$i][1] = new stdClass();
        $output['options'][$i][1]->option = Registry::load('strings')->view;
        $output['options'][$i][1]->class = 'load_aside';
        $output['options'][$i][1]->attributes['load'] = 'sticker_pack';
        $output['options'][$i][1]->attributes['data-sticker_pack'] = $pack_name;

        if (role(['permissions' => ['stickers' => 'edit']])) {
            $output['options'][$i][2] = new stdClass();
            $output['options'][$i][2]->option = Registry::load('strings')->edit;
            $output['options'][$i][2]->class = 'load_form';
            $output['options'][$i][2]->attributes['form'] = 'sticker_packs';
            $output['options'][$i][2]->attributes['data-sticker_pack'] = $pack_name;
        }

        if (role(['permissions' => ['stickers' => 'delete']])) {
            $output['options'][$i][3] = new stdClass();
            $output['options'][$i][3]->option = Registry::load('strings')->delete;
            $output['options'][$i][3]->class = 'ask_confirmation';
            $output['options'][$i][3]->attributes['data-info_box'] = true;
            $output['options'][$i][3]->attributes['data-remove'] = 'sticker_packs';
            $output['options'][$i][3]->attributes['data-sticker_pack'] = $pack_name;
            $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
        }

        $i++;
    }
}
?>