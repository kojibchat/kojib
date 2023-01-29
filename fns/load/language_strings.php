<?php

if (role(['permissions' => ['languages' => 'view']])) {

    if (empty($data["language_id"])) {
        $data["language_id"] = 1;
    }

    $data["language_id"] = filter_var($data["language_id"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data["language_id"])) {

        $columns = ['languages.name'];
        $where["languages.language_id"] = $data["language_id"];
        $language = DB::connect()->select('languages', $columns, $where);

        if (isset($language[0])) {
            $language = $language[0];
        } else {
            return;
        }

        $columns = $join = $where = null;
        $columns = [
            'language_strings.string_id', 'language_strings.string_constant', 'language_strings.string_value'
        ];

        if (!empty($data["offset"])) {
            $data["offset"] = array_map('intval', explode(',', $data["offset"]));
            $where["language_strings.string_id[!]"] = $data["offset"];
        }

        $where["language_strings.language_id"] = $data["language_id"];
        $where["language_strings.skip_update"] = 0;
        $where["language_strings.skip_cache"] = 0;

        if (!empty($data["search"])) {
            $where["AND #search_query"]["OR"] = [
                "language_strings.string_constant[~]" => $data["search"],
                "language_strings.string_value[~]" => $data["search"]
            ];
        }

        $where["LIMIT"] = Registry::load('settings')->records_per_call;

        if ($data["sortby"] === 'name_asc') {
            $where["ORDER"] = ["language_strings.string_constant" => "ASC"];
        } else if ($data["sortby"] === 'name_desc') {
            $where["ORDER"] = ["language_strings.string_constant" => "DESC"];
        } else {
            $where["ORDER"] = [
                "language_strings.string_id" => "ASC",
                "language_strings.skip_cache"
            ];
        }

        $strings = DB::connect()->select('language_strings', $columns, $where);

        $i = 1;
        $output = array();
        $output['loaded'] = new stdClass();
        $output['loaded']->title = $language['name'];
        $output['loaded']->offset = array();

        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }

        $output['sortby'][1] = new stdClass();
        $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
        $output['sortby'][1]->class = 'load_aside';
        $output['sortby'][1]->attributes['load'] = 'language_strings';
        $output['sortby'][1]->attributes['data-language_id'] = $data["language_id"];

        $output['sortby'][2] = new stdClass();
        $output['sortby'][2]->sortby = Registry::load('strings')->string_constant;
        $output['sortby'][2]->class = 'load_aside sort_asc';
        $output['sortby'][2]->attributes['load'] = 'language_strings';
        $output['sortby'][2]->attributes['sort'] = 'name_asc';
        $output['sortby'][2]->attributes['data-language_id'] = $data["language_id"];

        $output['sortby'][3] = new stdClass();
        $output['sortby'][3]->sortby = Registry::load('strings')->string_constant;
        $output['sortby'][3]->class = 'load_aside sort_desc';
        $output['sortby'][3]->attributes['load'] = 'language_strings';
        $output['sortby'][3]->attributes['sort'] = 'name_desc';
        $output['sortby'][3]->attributes['data-language_id'] = $data["language_id"];

        foreach ($strings as $string) {
            $output['loaded']->offset[] = $string['string_id'];

            $output['content'][$i] = new stdClass();
            $output['content'][$i]->image = get_image(['from' => 'languages', 'search' => $data['language_id']]);
            $output['content'][$i]->title = $string['string_constant'];
            $output['content'][$i]->class = "language_string";

            $output['content'][$i]->subtitle = strlen($string['string_value']) > 20 ? mb_substr($string['string_value'], 0, 20)."..." : $string['string_value'];

            $output['content'][$i]->icon = 0;
            $output['content'][$i]->unread = 0;

            if (role(['permissions' => ['languages' => 'edit']])) {
                $output['options'][$i][2] = new stdClass();
                $output['options'][$i][2]->option = Registry::load('strings')->edit;
                $output['options'][$i][2]->class = 'load_form';
                $output['options'][$i][2]->attributes['form'] = 'language_strings';
                $output['options'][$i][2]->attributes['data-string_id'] = $string['string_id'];
            }

            $i++;
        }
    }
}
?>