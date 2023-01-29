<?php

if (role(['permissions' => ['super_privileges' => 'slideshows']])) {

    include 'fns/filters/load.php';

    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->slideshows;

    $output['loaded']->loaded = 'slideshows';

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['loaded']->offset = intval($data["offset"])+intval(Registry::load('settings')->records_per_call);

    $location = 'assets/files/slideshows/*';

    if (!empty($data["search"])) {
        $data['search'] = rangeof_chars(sanitize_filename($data['search']));
        $location = $location.$data['search'].'*';
    }


    $slideshows = glob($location);
    $slideshows = array_slice($slideshows, $data["offset"], Registry::load('settings')->records_per_call);
    $i = 1;

    foreach ($slideshows as $slideshow) {
        $slideshow_name = basename($slideshow);
        $total_slideshows = new FilesystemIterator($slideshow, FilesystemIterator::SKIP_DOTS);
        $total_slideshows = iterator_count($total_slideshows);

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->class = "slideshow";

        $output['content'][$i]->alphaicon = true;

        $output['content'][$i]->title = ucwords(str_replace('_', ' ', $slideshow_name));
        $output['content'][$i]->subtitle = $total_slideshows.' '.Registry::load('strings')->images;
        $output['content'][$i]->identifier = $slideshow_name;
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        $output['options'][$i][1] = new stdClass();
        $output['options'][$i][1]->option = Registry::load('strings')->view;
        $output['options'][$i][1]->class = 'load_aside';
        $output['options'][$i][1]->attributes['load'] = 'slideshow_images';
        $output['options'][$i][1]->attributes['data-slideshow'] = $slideshow_name;

        $i++;
    }
}
?>