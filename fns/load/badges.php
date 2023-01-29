<?php

if (role(['permissions' => ['badges' => 'view']])) {

    $user_id = $group_id = 0;
    $join = null;

    if (isset($data['user_id'])) {
        $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
        $site_user = DB::connect()->select('site_users', ['site_users.display_name'], ['user_id' => $data["user_id"]]);
        if (isset($site_user[0])) {
            $site_user = $site_user[0];
            $user_id = $data["user_id"];
        }
    } else if (isset($data['group_id'])) {
        $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
        $group = DB::connect()->select('groups', ['groups.name'], ['group_id' => $data["group_id"]]);
        if (isset($group[0])) {
            $group = $group[0];
            $group_id = $data["group_id"];
        }
    }

    $columns = [
        'badges.badge_id', 'badges.string_constant',
        'badges.badge_category', 'badges.disabled'
    ];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["badges.badge_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        $join["[>]language_strings(string)"] = [
            "badges.string_constant" => "string_constant",
            "AND" => ["language_id" => Registry::load('current_user')->language]
        ];
        $where["string.string_value[~]"] = $data["search"];
    }


    if (!empty($user_id)) {

        $columns[] = 'badges_assigned.badge_assigned_id';
        $join["[>]badges_assigned"] = ["badges.badge_id" => "badge_id", "AND" => ["user_id" => $user_id]];

        $where["badges.disabled"] = 0;
        $where["badges.badge_category"] = 'profile';
    } else if (!empty($group_id)) {

        $columns[] = 'badges_assigned.badge_assigned_id';
        $join["[>]badges_assigned"] = ["badges.badge_id" => "badge_id", "AND" => ["group_id" => $group_id]];

        $where["badges.disabled"] = 0;
        $where["badges.badge_category"] = 'group';
    }

    $where["ORDER"] = ["badges.badge_id" => "DESC"];
    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if (!empty($join)) {
        $badges = DB::connect()->select('badges', $join, $columns, $where);
    } else {
        $badges = DB::connect()->select('badges', $columns, $where);
    }

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->badges;
    $output['loaded']->loaded = 'badges';
    $output['loaded']->offset = array();


    if (role(['permissions' => ['badges' => 'delete']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'badges';
        $output['multiple_select']->attributes['multi_select'] = 'badge_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }


    if (empty($user_id) && empty($group_id)) {
        if (role(['permissions' => ['badges' => 'create']])) {
            $output['todo'] = new stdClass();
            $output['todo']->class = 'load_form';
            $output['todo']->title = Registry::load('strings')->create_badge;
            $output['todo']->attributes['form'] = 'badges';
        }
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    if (!empty($user_id)) {
        $output['loaded']->title = $site_user['display_name'].' ['.$output['loaded']->title.']';
    } else if (!empty($group_id)) {
        $output['loaded']->title = $group['name'].' ['.$output['loaded']->title.']';
    }

    foreach ($badges as $badge) {
        $output['loaded']->offset[] = $badge['badge_id'];
        $string_constant = $badge['string_constant'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'badges', 'search' => $badge['badge_id']]);
        $output['content'][$i]->title = Registry::load('strings')->$string_constant;
        $output['content'][$i]->identifier = $badge['badge_id'];
        $output['content'][$i]->class = "badges";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        if ((int)$badge['disabled'] === 1) {
            $output['content'][$i]->subtitle = Registry::load('strings')->disabled;
        } else {
            $output['content'][$i]->subtitle = Registry::load('strings')->enabled;
        }

        if (!empty($user_id) || !empty($group_id)) {

            if (!isset($badge['badge_assigned_id']) || empty($badge['badge_assigned_id'])) {

                $output['content'][$i]->subtitle = Registry::load('strings')->not_assigned;

                if (role(['permissions' => ['badges' => 'assign']])) {
                    $output['options'][$i][2] = new stdClass();
                    $output['options'][$i][2]->option = Registry::load('strings')->assign;
                    $output['options'][$i][2]->class = 'ask_confirmation';
                    $output['options'][$i][2]->attributes['data-badge_id'] = $badge['badge_id'];
                    $output['options'][$i][2]->attributes['submit_button'] = Registry::load('strings')->yes;
                    $output['options'][$i][2]->attributes['cancel_button'] = Registry::load('strings')->no;
                    $output['options'][$i][2]->attributes['confirmation'] = Registry::load('strings')->confirm_action;

                    if (!empty($user_id)) {
                        $output['options'][$i][2]->attributes['data-add'] = 'site_user_badges';
                        $output['options'][$i][2]->attributes['data-user_id'] = $user_id;
                    } else {
                        $output['options'][$i][2]->attributes['data-add'] = 'group_badges';
                        $output['options'][$i][2]->attributes['data-group_id'] = $group_id;
                    }
                }

            } else {
                $output['content'][$i]->subtitle = Registry::load('strings')->assigned;

                if (role(['permissions' => ['badges' => 'assign']])) {
                    $output['options'][$i][2] = new stdClass();
                    $output['options'][$i][2]->option = Registry::load('strings')->remove;
                    $output['options'][$i][2]->class = 'ask_confirmation';
                    $output['options'][$i][2]->attributes['data-badge_id'] = $badge['badge_id'];
                    $output['options'][$i][2]->attributes['submit_button'] = Registry::load('strings')->yes;
                    $output['options'][$i][2]->attributes['cancel_button'] = Registry::load('strings')->no;
                    $output['options'][$i][2]->attributes['confirmation'] = Registry::load('strings')->confirm_action;

                    if (!empty($user_id)) {
                        $output['options'][$i][2]->attributes['data-remove'] = 'site_user_badges';
                        $output['options'][$i][2]->attributes['data-user_id'] = $user_id;
                    } else {
                        $output['options'][$i][2]->attributes['data-remove'] = 'group_badges';
                        $output['options'][$i][2]->attributes['data-group_id'] = $group_id;
                    }
                }
            }

        } else {

            $output['options'][$i][1] = new stdClass();
            $output['options'][$i][1]->option = Registry::load('strings')->assigned;
            $output['options'][$i][1]->class = 'load_aside';
            $output['options'][$i][1]->attributes['load'] = 'badges_assigned';
            $output['options'][$i][1]->attributes['data-badge_id'] = $badge['badge_id'];

            if (role(['permissions' => ['badges' => 'edit']])) {
                $output['options'][$i][2] = new stdClass();
                $output['options'][$i][2]->option = Registry::load('strings')->edit;
                $output['options'][$i][2]->class = 'load_form';
                $output['options'][$i][2]->attributes['form'] = 'badges';
                $output['options'][$i][2]->attributes['data-badge_id'] = $badge['badge_id'];
            }

            if (role(['permissions' => ['badges' => 'delete']])) {
                $output['options'][$i][3] = new stdClass();
                $output['options'][$i][3]->option = Registry::load('strings')->delete;
                $output['options'][$i][3]->class = 'ask_confirmation';
                $output['options'][$i][3]->attributes['data-remove'] = 'badges';
                $output['options'][$i][3]->attributes['data-badge_id'] = $badge['badge_id'];
                $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
                $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            }
        }

        $i++;
    }
}
?>