<?php

if (role(['permissions' => ['custom_menu' => 'view']])) {

    $join = null;
    $columns = [
        'custom_menu_items.menu_item_id', 'custom_menu_items.string_constant',
        'custom_menu_items.disabled'
    ];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["custom_menu_items.menu_item_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {

        $join["[>]language_strings(string)"] = [
            "custom_menu_items.string_constant" => "string_constant",
            "AND" => ["language_id" => Registry::load('current_user')->language]
        ];
        $where["string.string_value[~]"] = $data["search"];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    $where["ORDER"] = ["custom_menu_items.menu_item_id" => "DESC"];

    if (!empty($join)) {
        $menu_items = DB::connect()->select('custom_menu_items', $join, $columns, $where);
    } else {
        $menu_items = DB::connect()->select('custom_menu_items', $columns, $where);
    }

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->menu_items;
    $output['loaded']->loaded = 'custom_menu_items';
    $output['loaded']->offset = array();

    if (role(['permissions' => ['custom_menu' => 'delete']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'custom_menu_items';
        $output['multiple_select']->attributes['multi_select'] = 'menu_item_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    if (role(['permissions' => ['custom_menu' => 'create']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->add_menu_item;
        $output['todo']->attributes['form'] = 'custom_menu_items';
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    foreach ($menu_items as $menu_item) {
        $output['loaded']->offset[] = $menu_item['menu_item_id'];
        
        $string_constant = $menu_item['string_constant'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->alphaicon = true;
        $output['content'][$i]->title = Registry::load('strings')->$string_constant;
        $output['content'][$i]->identifier = $menu_item['menu_item_id'];
        $output['content'][$i]->class = "custom_menu_item";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        if ((int)$menu_item['disabled'] === 1) {
            $output['content'][$i]->subtitle = Registry::load('strings')->disabled;
        } else {
            $output['content'][$i]->subtitle = Registry::load('strings')->enabled;
        }

        if (role(['permissions' => ['custom_menu' => 'edit']])) {
            $output['options'][$i][2] = new stdClass();
            $output['options'][$i][2]->option = Registry::load('strings')->edit;
            $output['options'][$i][2]->class = 'load_form';
            $output['options'][$i][2]->attributes['form'] = 'custom_menu_items';
            $output['options'][$i][2]->attributes['data-menu_item_id'] = $menu_item['menu_item_id'];
        }


        if (role(['permissions' => ['custom_menu' => 'delete']])) {
            $output['options'][$i][3] = new stdClass();
            $output['options'][$i][3]->option = Registry::load('strings')->delete;
            $output['options'][$i][3]->class = 'ask_confirmation';
            $output['options'][$i][3]->attributes['data-remove'] = 'custom_menu_items';
            $output['options'][$i][3]->attributes['data-menu_item_id'] = $menu_item['menu_item_id'];
            $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        }


        $i++;
    }
}
?>