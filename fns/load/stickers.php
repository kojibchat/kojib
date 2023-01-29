<?php

if (role(['permissions' => ['private_conversations' => 'attach_stickers', 'groups' => 'attach_stickers'], 'condition' => 'OR'])) {

    include('fns/filters/load.php');

    $sticker_packs = glob('assets/files/stickers/*', GLOB_ONLYDIR);

    $i = 0;

    foreach ($sticker_packs as $pack) {

        $output['subtabs'][$i] = new stdClass();

        $sticker_icon = $pack.'/sticker_pack_icon.png';

        if (file_exists($sticker_icon)) {
            $output['subtabs'][$i]->image = Registry::load('config')->site_url.$sticker_icon;
        } else {
            $output['subtabs'][$i]->image = Registry::load('config')->site_url.'assets/files/defaults/stickers.png';
        }

        $output['subtabs'][$i]->class = "load_grid_list";
        $output['subtabs'][$i]->attributes = ['load' => 'stickers_module', 'data-sticker_pack' => basename($pack), 'reload' => true];
        $i = $i+1;

    }
    if (!isset($data["sticker_pack"]) || empty($data["sticker_pack"])) {

        $data["sticker_pack"] = 0;

        if (count($sticker_packs) > 0) {
            $data["sticker_pack"] = basename($sticker_packs[0]);
        }
    } else {
        $data["sticker_pack"] = sanitize_filename($data['sticker_pack']);
    }

    $extensions = rangeof_chars('jpg,png,gif,jpeg,bmp');
    $location = 'assets/files/stickers/'.$data["sticker_pack"].'/*.{'.$extensions.'}';

    $stickers = glob($location, GLOB_BRACE);

    $offset = 0;

    $data["offset"] = filter_var($data["offset"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data["offset"])) {
        $offset = $data["offset"];
    }

    $stickers = array_slice($stickers, $offset, 25);

    if (!empty($stickers)) {
        $output['offset'] = $offset+25;
    } else {
        $output['offset'] = 'endofresults';
    }

    $i = 0;

    foreach ($stickers as $sticker) {
        $sticker_name = basename($sticker);
        if ($sticker_name !== "sticker_pack_icon.png") {
            $output['content'][$i] = new stdClass();
            $output['content'][$i]->image = Registry::load('config')->site_url.$sticker;
            $output['content'][$i]->class = "send_sticker";
            $output['content'][$i]->attributes = ['sticker_pack' => $data["sticker_pack"], 'sticker' => $sticker_name];
            $i = $i+1;
        }
    }
}