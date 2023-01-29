<?php

if (role(['permissions' => ['avatars' => 'view']])) {

    include 'fns/filters/load.php';
    include 'fns/files/load.php';

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->avatars;
    $output['loaded']->loaded = 'avatars';

    if (role(['permissions' => ['avatars' => 'delete']])) {

        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'avatars';
        $output['multiple_select']->attributes['multi_select'] = 'avatar';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['loaded']->offset = intval($data["offset"])+intval(Registry::load('settings')->records_per_call);

    if (role(['permissions' => ['avatars' => 'upload']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->upload_avatar;
        $output['todo']->attributes['form'] = 'avatars';
    }

    $location = 'assets/files/avatars/*';

    if (!empty($data["search"])) {
        $data['search'] = rangeof_chars(sanitize_filename($data['search']));
        $location = $location.$data['search'].'*';
    }

    $extensions = rangeof_chars('jpg,png,gif,jpeg,bmp');
    $location = $location.'.{'.$extensions.'}';

    $avatars = glob($location, GLOB_BRACE);
    
    usort($avatars, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $avatars = array_slice($avatars, $data["offset"], Registry::load('settings')->records_per_call);

    foreach ($avatars as $avatar) {
        $avatar_name = basename($avatar);
        $output['content'][$i] = new stdClass();
        $output['content'][$i]->class = "avatar";
        $output['content'][$i]->image = Registry::load('config')->site_url.$avatar;
        $output['content'][$i]->identifier = $avatar_name;

        $output['content'][$i]->title = $avatar_name;
        $output['content'][$i]->subtitle = files('getsize', ['getsize_of' => $avatar, 'real_path' => true]);
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        if (role(['permissions' => ['avatars' => 'delete']])) {
            $output['options'][$i][3] = new stdClass();
            $output['options'][$i][3]->option = Registry::load('strings')->delete;
            $output['options'][$i][3]->class = 'ask_confirmation';
            $output['options'][$i][3]->attributes['data-remove'] = 'avatars';
            $output['options'][$i][3]->attributes['data-avatar'] = $avatar_name;
            $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
        }

        $i++;
    }
}
?>