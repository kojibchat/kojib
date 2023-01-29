<?php

$result['mime_type'] = 'video/vimeo';

if (isset($link_meta_data['title'])) {
    $result['title'] = $link_meta_data['title'];
    $result['image'] = $link_meta_data['thumbnail_url'];

    if (isset($link_meta_data['description'])) {
        $result['description'] = $link_meta_data['description'];
    } else if (isset($link_meta_data['author_name'])) {
        $result['description'] = $link_meta_data['author_name'];
    }
}