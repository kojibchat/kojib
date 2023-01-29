<?php

if (role(['permissions' => ['site_notifications' => 'view']])) {


    $columns = [
        'site_notifications.notification_id', 'site_notifications.related_user_id', 'site_notifications.related_group_id',
        'site_notifications.related_message_id', 'site_notifications.notification_type', 'site_users.display_name',
        'site_notifications.related_parameters', 'groups.name(group_name)'
    ];

    if (role(['permissions' => ['site_notifications' => 'view']])) {


        $columns = [
            'site_notifications.notification_id', 'site_notifications.related_user_id', 'site_notifications.related_group_id',
            'site_notifications.related_message_id', 'site_notifications.notification_type', 'site_users.display_name',
            'site_notifications.related_parameters', 'groups.name(group_name)'
        ];

        $join["[>]site_users"] = ["site_notifications.related_user_id" => "user_id"];
        $join["[>]groups"] = ["site_notifications.related_group_id" => "group_id"];

        if (!empty($data["offset"])) {
            $data["offset"] = array_map('intval', explode(',', $data["offset"]));
            $where["site_notifications.notification_id[!]"] = $data["offset"];
        }

        if (!empty($data["search"])) {
            $where["string.string_value[~]"] = $data["search"];
        }

        $where["site_notifications.user_id"] = Registry::load('current_user')->id;
        $where["LIMIT"] = Registry::load('settings')->records_per_call;

        $where["ORDER"] = ["site_notifications.notification_id" => "DESC"];

        $notifications = DB::connect()->select('site_notifications', $join, $columns, $where);

        $i = 1;
        $output = array();
        $output['loaded'] = new stdClass();
        $output['loaded']->title = Registry::load('strings')->notifications;
        $output['loaded']->loaded = 'notifications';
        $output['loaded']->offset = array();

        if (role(['permissions' => ['site_notifications' => 'delete']])) {
            $output['multiple_select'] = new stdClass();
            $output['multiple_select']->title = Registry::load('strings')->delete;
            $output['multiple_select']->attributes['class'] = 'ask_confirmation';
            $output['multiple_select']->attributes['data-remove'] = 'site_notifications';
            $output['multiple_select']->attributes['multi_select'] = 'notification_id';
            $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        }

        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }

        foreach ($notifications as $notification) {
            $output['loaded']->offset[] = $notification['notification_id'];

            $output['content'][$i] = new stdClass();
            $output['content'][$i]->identifier = $notification['notification_id'];
            $output['content'][$i]->title = $notification['display_name'];
            $output['content'][$i]->class = "site_notification";
            $output['content'][$i]->icon = 0;
            $output['content'][$i]->unread = 0;

            if (empty($output['content'][$i]->title)) {
                $output['content'][$i]->title = Registry::load('strings')->unknown;
            }

            $notification_type = $notification['notification_type'];
            $output['content'][$i]->subtitle = Registry::load('strings')->$notification_type;

            if ($notification_type === 'group_invitation' && !empty($notification['group_name'])) {
                $output['content'][$i]->subtitle .= ' - '.$notification['group_name'];
            }

            if (!empty($notification['related_parameters'])) {
                $related_parameters = json_decode($notification['related_parameters']);
            }

            if (isset($related_parameters) && isset($related_parameters->badge_id) && !empty($related_parameters->badge_id)) {
                $output['content'][$i]->image = get_image(['from' => 'badges', 'search' => $related_parameters->badge_id]);
            } else if (!empty($notification['related_user_id'])) {
                $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $notification['related_user_id']]);
            } else if (!empty($notification['related_group_id'])) {
                $output['content'][$i]->image = get_image(['from' => 'groups/icons', 'search' => $notification['related_group_id']]);
            } else {
                $output['content'][$i]->alphaicon = true;
            }

            $option_index = 1;

            if (role(['permissions' => ['groups' => 'join_group']]) && isset($related_parameters) && isset($related_parameters->invitation_id) && !empty($related_parameters->invitation_id)) {

                $invitation_link = Registry::load('config')->site_url.'group/'.$notification['related_group_id'].'/invitation/';
                $invitation_link .= $related_parameters->invitation_id.'/'.$related_parameters->invitation_code.'/';

                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->join_group;
                $output['options'][$i][$option_index]->class = 'open_link';
                $output['options'][$i][$option_index]->attributes['link'] = $invitation_link;
                $option_index++;

            } else if (!empty($notification['related_group_id'])) {
                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->view_group;
                $output['options'][$i][$option_index]->class = 'load_conversation';
                $output['options'][$i][$option_index]->attributes['group_id'] = $notification['related_group_id'];
                $option_index++;
            } else if (!empty($notification['related_user_id'])) {
                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->profile;
                $output['options'][$i][$option_index]->class = 'get_info';
                $output['options'][$i][$option_index]->attributes['user_id'] = $notification['related_user_id'];
                $option_index++;
            }

            if (role(['permissions' => ['site_notifications' => 'delete']])) {
                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->delete;
                $output['options'][$i][$option_index]->class = 'ask_confirmation';
                $output['options'][$i][$option_index]->attributes['data-remove'] = 'site_notifications';
                $output['options'][$i][$option_index]->attributes['data-notification_id'] = $notification['notification_id'];
                $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            }

            $option_index++;

            $i++;
        }

        if (empty($data["offset"])) {
            DB::connect()->update("site_notifications", ["read_status" => 1], [
                'user_id' => Registry::load('current_user')->id
            ]);
        }
    }
}
?>