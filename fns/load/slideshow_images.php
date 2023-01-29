<?php

if (role(['permissions' => ['super_privileges' => 'slideshows']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->slideshows;
    $output['loaded']->loaded = 'slideshow_images';

    if (isset($data["slideshow"]) && !empty($data["slideshow"])) {

        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }

        $data["slideshow"] = sanitize_filename($data['slideshow']);

        if (!empty($data["slideshow"])) {

            $output['loaded']->offset = intval($data["offset"])+intval(Registry::load('settings')->records_per_call);

            $output['multiple_select'] = new stdClass();
            $output['multiple_select']->title = Registry::load('strings')->delete;
            $output['multiple_select']->attributes['class'] = 'ask_confirmation';
            $output['multiple_select']->attributes['data-remove'] = 'slideshow_images';
            $output['multiple_select']->attributes['data-slideshow'] = $data['slideshow'];
            $output['multiple_select']->attributes['multi_select'] = 'slideshow_image';
            $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;

            $location = 'assets/files/slideshows/'.$data["slideshow"].'/*';

            if (!empty($data["search"])) {
                $data['search'] = rangeof_chars(sanitize_filename($data['search']));
                $location = $location.$data['search'].'*';
            }

            $extensions = rangeof_chars('jpg,png,gif,jpeg,bmp');
            $location = $location.'.{'.$extensions.'}';

            $slideshow_images = glob($location, GLOB_BRACE);
            $slideshow_images = array_slice($slideshow_images, $data["offset"], Registry::load('settings')->records_per_call);

            $output['todo'] = new stdClass();
            $output['todo']->class = 'load_form';
            $output['todo']->title = Registry::load('strings')->add_images;
            $output['todo']->attributes['form'] = 'slideshow_images';
            $output['todo']->attributes['data-slideshow'] = $data["slideshow"];

            $i = 1;

            foreach ($slideshow_images as $slideshow_image) {
                $slideshow_name = basename($slideshow_image);

                $output['content'][$i] = new stdClass();
                $output['content'][$i]->class = "slideshow_image";

                $output['content'][$i]->title = $slideshow_name;
                $output['content'][$i]->subtitle = files('getsize', ['getsize_of' => $slideshow_image, 'real_path' => true]);

                $output['content'][$i]->image = Registry::load('config')->site_url.$slideshow_image;

                $output['content'][$i]->identifier = $slideshow_name;
                $output['content'][$i]->icon = 0;
                $output['content'][$i]->unread = 0;

                $output['options'][$i][1] = new stdClass();
                $output['options'][$i][1]->option = Registry::load('strings')->delete;
                $output['options'][$i][1]->class = 'ask_confirmation';
                $output['options'][$i][1]->attributes['data-remove'] = 'slideshow_images';
                $output['options'][$i][1]->attributes['data-slideshow'] = $data['slideshow'];
                $output['options'][$i][1]->attributes['data-slideshow_image'] = $slideshow_name;
                $output['options'][$i][1]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                $output['options'][$i][1]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][1]->attributes['cancel_button'] = Registry::load('strings')->no;

                $i++;
            }
        }
    }
}
?>