<?php

if (role(['permissions' => ['badges' => 'view']])) {

    $badge_id = 0;

    if (isset($data['badge_id'])) {
        $data["badge_id"] = filter_var($data["badge_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data["badge_id"])) {

            $columns = $join = $where = null;
            $columns = [
                'badges.badge_id', 'badges.string_constant',
                'badges.badge_category',
            ];

            $where["badges.badge_id"] = $data["badge_id"];

            $where["LIMIT"] = 1;


            $badge = DB::connect()->select('badges', $columns, $where);

            if (isset($badge[0])) {
                $badge = $badge[0];
                $badge_id = $data["badge_id"];
            }
        }
    }
    if (!empty($badge_id)) {

        $columns = $join = $where = null;

        if (!empty($data["offset"])) {
            $data["offset"] = array_map('intval', explode(',', $data["offset"]));
            $where["badges.badge_id[!]"] = $data["offset"];
        }


        if (isset($badge['badge_category']) && $badge['badge_category'] === 'profile') {
            $columns = [
                'site_users.display_name(title)', 'site_users.username(subtitle)', 'badges_assigned.user_id',
                'badges_assigned.badge_assigned_id'
            ];
            $join["[>]site_users"] = ["badges_assigned.user_id" => "user_id"];
            $where["badges_assigned.badge_id"] = $badge_id;

            if (!empty($data["search"])) {
                $where["AND #search_query"]["OR"] = [
                    "site_users.display_name[~]" => $data["search"],
                    "site_users.username[~]" => $data["search"]
                ];
            }
        } else {
            $columns = [
                'groups.name(title)', 'groups.slug(subtitle)', 'badges_assigned.group_id',
                'badges_assigned.badge_assigned_id'
            ];
            $join["[>]groups"] = ["badges_assigned.group_id" => "group_id"];
            $where["badges_assigned.badge_id"] = $badge_id;

            if (!empty($data["search"])) {
                $where["AND #search_query"]["OR"] = [
                    "groups.name[~]" => $data["search"],
                    "groups.slug[~]" => $data["search"]
                ];
            }
        }

        $where["ORDER"] = ["badges_assigned.assigned_on" => "DESC"];
        $where["LIMIT"] = Registry::load('settings')->records_per_call;


        $assigned_badges = DB::connect()->select('badges_assigned', $join, $columns, $where);

        $i = 1;
        $string_constant = $badge['string_constant'];

        $output = array();
        $output['loaded'] = new stdClass();
        $output['loaded']->title = Registry::load('strings')->$string_constant;
        $output['loaded']->loaded = 'badges';
        $output['loaded']->offset = array();

        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }

        foreach ($assigned_badges as $assigned_badge) {
            $output['loaded']->offset[] = $assigned_badge['badge_assigned_id'];

            $output['content'][$i] = new stdClass();
            $output['content'][$i]->title = $assigned_badge['title'];
            $output['content'][$i]->identifier = $assigned_badge['badge_assigned_id'];
            $output['content'][$i]->class = "badges";
            $output['content'][$i]->icon = 0;
            $output['content'][$i]->unread = 0;

            if (isset($assigned_badge['subtitle']) && !empty($assigned_badge['subtitle'])) {
                $output['content'][$i]->subtitle = $assigned_badge['subtitle'];
            } else {
                $output['content'][$i]->subtitle = Registry::load('strings')->group;
            }

            if (isset($assigned_badge['badge_assigned_id']) && !empty($assigned_badge['badge_assigned_id'])) {

                if (isset($badge['badge_category']) && $badge['badge_category'] === 'profile') {
                    $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $assigned_badge['user_id']]);

                } else {
                    $output['content'][$i]->image = get_image(['from' => 'groups/icons', 'search' => $assigned_badge['group_id']]);
                }

                if (role(['permissions' => ['badges' => 'assign']])) {
                    $output['options'][$i][2] = new stdClass();
                    $output['options'][$i][2]->option = Registry::load('strings')->remove;
                    $output['options'][$i][2]->class = 'ask_confirmation';
                    $output['options'][$i][2]->attributes['data-badge_id'] = $badge['badge_id'];
                    $output['options'][$i][2]->attributes['submit_button'] = Registry::load('strings')->yes;
                    $output['options'][$i][2]->attributes['cancel_button'] = Registry::load('strings')->no;
                    $output['options'][$i][2]->attributes['confirmation'] = Registry::load('strings')->confirm_action;

                    if (isset($badge['badge_category']) && $badge['badge_category'] === 'profile') {
                        $output['options'][$i][2]->attributes['data-remove'] = 'site_user_badges';
                        $output['options'][$i][2]->attributes['data-user_id'] = $assigned_badge['user_id'];

                    } else {
                        $output['options'][$i][2]->attributes['data-remove'] = 'group_badges';
                        $output['options'][$i][2]->attributes['data-group_id'] = $assigned_badge['group_id'];
                    }
                }
            }

            $i++;
        }
    }
}
?>