<?php

if (role(['permissions' => ['stickers' => 'view']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';


    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->stickers;


    if (isset($data["sticker_pack"]) && !empty($data["sticker_pack"])) {
        $data["sticker_pack"] = sanitize_filename($data['sticker_pack']);
    }

    if (isset($data["sticker_pack"]) && !empty($data["sticker_pack"])) {
        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }

        $output['loaded']->title = $data["sticker_pack"];
        $output['loaded']->offset = intval($data["offset"])+intval(Registry::load('settings')->records_per_call);

        if (role(['permissions' => ['stickers' => 'edit']])) {
            $output['todo'] = new stdClass();
            $output['todo']->class = 'load_form';
            $output['todo']->title = Registry::load('strings')->edit_sticker_pack;
            $output['todo']->attributes['form'] = 'sticker_packs';
            $output['todo']->attributes['data-sticker_pack'] = $data["sticker_pack"];
        }

        if (role(['permissions' => ['stickers' => 'delete']])) {
            $output['multiple_select'] = new stdClass();
            $output['multiple_select']->title = Registry::load('strings')->delete;
            $output['multiple_select']->attributes['class'] = 'ask_confirmation';
            $output['multiple_select']->attributes['data-remove'] = 'sticker_packs';
            $output['multiple_select']->attributes['data-sticker_pack'] = $data["sticker_pack"];
            $output['multiple_select']->attributes['multi_select'] = 'sticker';
            $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        }

        $location = 'assets/files/stickers/'.$data["sticker_pack"].'/*';

        if (!empty($data["search"])) {
            $data['search'] = rangeof_chars(sanitize_filename($data['search']));
            $location = $location.$data['search'].'*';
        }

        $extensions = rangeof_chars('jpg,png,gif,jpeg,bmp');
        $location = $location.'.{'.$extensions.'}';

        $stickers = glob($location, GLOB_BRACE);
        $stickers = array_slice($stickers, $data["offset"], Registry::load('settings')->records_per_call);

        $i = 1;

        foreach ($stickers as $sticker) {
            $sticker_name = basename($sticker);
            if ($sticker_name != 'sticker_pack_icon.png') {
                $total_stickers = 0;
                $output['content'][$i] = new stdClass();
                $output['content'][$i]->class = "stickerpack";
                $output['content'][$i]->image = Registry::load('config')->site_url.$sticker;
                $output['content'][$i]->identifier = $sticker_name;

                $output['content'][$i]->title = str_replace('.png', '', $sticker_name);
                $output['content'][$i]->subtitle = files('getsize', ['getsize_of' => $sticker, 'real_path' => true]);
                $output['content'][$i]->icon = 0;
                $output['content'][$i]->unread = 0;

                if (role(['permissions' => ['stickers' => 'delete']])) {
                    $output['options'][$i][3] = new stdClass();
                    $output['options'][$i][3]->option = Registry::load('strings')->delete;
                    $output['options'][$i][3]->class = 'ask_confirmation';
                    $output['options'][$i][3]->attributes['data-remove'] = 'sticker_packs';
                    $output['options'][$i][3]->attributes['data-sticker'] = $sticker_name;
                    $output['options'][$i][3]->attributes['data-sticker_pack'] = $data["sticker_pack"];
                    $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                    $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
                    $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
                }

                $i++;
            }
        }
    }
}
?>