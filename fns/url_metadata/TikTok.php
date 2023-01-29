<?php

if (isset($link_meta_data['title'])) {
    $result['title'] = $link_meta_data['title'];
    $result['image'] = $link_meta_data['thumbnail_url'];

    if (isset($link_meta_data['description'])) {
        $result['description'] = $link_meta_data['description'];
    } else if (isset($link_meta_data['author_name'])) {
        $result['description'] = $link_meta_data['author_name'];
    }
    if (isset($link_meta_data['html'])) {
        $doc = new DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($link_meta_data['html'], 'HTML-ENTITIES', 'UTF-8'));
        $finder = new DOMXPath($doc);
        $nodes = $doc->getElementsByTagName('blockquote');
        if (!empty($nodes->item(0)->getAttribute('data-video-id'))) {
            $result['iframe_embed'] = 'https://www.tiktok.com/embed/'.$nodes->item(0)->getAttribute('data-video-id');
            $result['iframe_class'] = 'w-auto h-75';
        }
    }
}