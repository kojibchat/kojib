<?php

if (role(['permissions' => ['complaints' => ['track_status', 'review_complaints']], 'condition' => 'OR'])) {

    $columns = [
        'complaints.complaint_id', 'complaints.user_id', 'complaints.group_id',
        'site_users.display_name', 'groups.name(group_name)', 'complaints.complaint_status',
        'group_messages.group_message_id'
    ];

    $join["[>]site_users"] = ["complaints.user_id" => "user_id"];
    $join["[>]group_messages"] = ["complaints.group_message_id" => "group_message_id"];
    $join["[>]groups"] = ["complaints.group_id" => "group_id"];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["complaints.complaint_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {

        $id_search = filter_var($data["search"], FILTER_SANITIZE_NUMBER_INT);

        if (empty($id_search)) {
            $id_search = 0;
        }

        $where["AND #search_query"] = [
            "OR" => [
                "groups.name[~]" => $data["search"],
                "site_users.display_name[~]" => $data["search"],
                "complaints.complaint_id" => $id_search
            ]
        ];
    }

    if (role(['permissions' => ['complaints' => 'review_complaints']])) {
        $where["complaints.complaint_id[!]"] = 0;
    } else if (role(['permissions' => ['complaints' => 'track_status']])) {
        $where["complaints.complainant_user_id"] = Registry::load('current_user')->id;
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    $where["ORDER"] = ["complaints.complaint_status" => "ASC", "complaints.complaint_id" => "DESC"];

    $complaints = DB::connect()->select('complaints', $join, $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->complaints;
    $output['loaded']->loaded = 'complaints';
    $output['loaded']->offset = array();

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    if (role(['permissions' => ['complaints' => 'review_complaints']])) {
        if (role(['permissions' => ['complaints' => 'delete_complaints']])) {
            $output['multiple_select'] = new stdClass();
            $output['multiple_select']->title = Registry::load('strings')->delete;
            $output['multiple_select']->attributes['class'] = 'ask_confirmation';
            $output['multiple_select']->attributes['data-remove'] = 'complaints';
            $output['multiple_select']->attributes['multi_select'] = 'complaint_id';
            $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        }
    }

    foreach ($complaints as $complaint) {
        $output['loaded']->offset[] = $complaint['complaint_id'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->identifier = $complaint['complaint_id'];
        $output['content'][$i]->title = 'COMP#'.$complaint['complaint_id'];
        $output['content'][$i]->class = "complaint";
        $output['content'][$i]->subtitle = Registry::load('strings')->under_review;

        if ((int)$complaint['complaint_status'] === 1) {
            $output['content'][$i]->subtitle = Registry::load('strings')->action_taken;
        } else if ((int)$complaint['complaint_status'] === 2) {
            $output['content'][$i]->subtitle = Registry::load('strings')->rejected;
        }

        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        if (!empty($complaint['user_id'])) {
            $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $complaint['user_id']]);
        } else if (!empty($complaint['group_id'])) {
            $output['content'][$i]->image = get_image(['from' => 'groups/icons', 'search' => $complaint['group_id']]);
        } else {
            $output['content'][$i]->alphaicon = true;
        }

        $option_index = 1;


        if (role(['permissions' => ['complaints' => 'review_complaints']])) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->review;
            $output['options'][$i][$option_index]->class = 'load_form';
            $output['options'][$i][$option_index]->attributes['form'] = 'site_user_complaint';
            $output['options'][$i][$option_index]->attributes['data-complaint_id'] = $complaint['complaint_id'];
            $option_index++;

            if (!empty($complaint['user_id'])) {
                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->profile;
                $output['options'][$i][$option_index]->class = 'get_info';
                $output['options'][$i][$option_index]->attributes['user_id'] = $complaint['user_id'];
                $option_index++;
            } else if (!empty($complaint['group_id'])) {
                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->view_group;
                $output['options'][$i][$option_index]->class = 'load_conversation';
                $output['options'][$i][$option_index]->attributes['group_id'] = $complaint['group_id'];

                if (!empty($complaint['group_message_id'])) {
                    $output['options'][$i][$option_index]->option = Registry::load('strings')->view_message;
                    $output['options'][$i][$option_index]->attributes['search'] = $complaint['group_message_id'];
                    $output['options'][$i][$option_index]->attributes['search_message_id'] = true;
                }

                $option_index++;
            }

            if (role(['permissions' => ['complaints' => 'delete_complaints']])) {
                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->delete;
                $output['options'][$i][$option_index]->class = 'ask_confirmation';
                $output['options'][$i][$option_index]->attributes['data-info_box'] = true;
                $output['options'][$i][$option_index]->attributes['data-remove'] = 'complaints';
                $output['options'][$i][$option_index]->attributes['data-complaint_id'] = $complaint['complaint_id'];
                $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                $option_index++;
            }

        } else {

            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->view;
            $output['options'][$i][$option_index]->class = 'load_form';
            $output['options'][$i][$option_index]->attributes['form'] = 'site_user_complaint';
            $output['options'][$i][$option_index]->attributes['data-complaint_id'] = $complaint['complaint_id'];
            $option_index++;
        }

        $i++;
    }
}
?>