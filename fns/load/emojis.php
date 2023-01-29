<?php

include('fns/emoji/Emoji_list.php');

if (!empty($data["search"])) {
    $search_text = htmlspecialchars($data["search"]);
    $output['offset'] = 'endofresults';
} else {
    $offset = 0;

    $data["offset"] = filter_var($data["offset"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data["offset"])) {
        $offset = $data["offset"];
    }

    $emojis = array_slice($emojis, $offset, 200);

    if (!empty($emojis)) {
        $output['offset'] = $offset+200;
    } else {
        $output['offset'] = 'endofresults';
    }

    $search_text = '';

}


$emojis = array_unique($emojis);
$i = 0;
foreach ($emojis as $emoji_name => $emoji_img) {
    $hide_emoji = false;

    if (!empty($search_text) && stripos($emoji_name, $search_text) === false) {
        $hide_emoji = true;
    }

    if (!$hide_emoji) {
        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = $emoji_img;

        $output['content'][$i]->class = "add_emoji";
        $output['content'][$i]->attributes = ['emoji' => $emoji_name];
        $i = $i+1;
    }
}