<?php
$group_id = 0;
$skip_meta_tags = false;

if (isset(Registry::load('config')->load_group_conversation) && !empty(Registry::load('config')->load_group_conversation)) {
    $group_id = Registry::load('config')->load_group_conversation;
}

if (!empty($group_id)) {
    $columns = $join = $where = null;
    $columns = [
        'groups.group_id', 'groups.name', 'groups.slug', 'groups.secret_group', 'groups.secret_code',
        'groups.description', 'groups.meta_title', 'groups.meta_description', 'groups.password',
        'group_members.group_member_id',
    ];
    $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => $user_id]];

    $where["groups.group_id"] = $group_id;
    $where["LIMIT"] = 1;


    $group = DB::connect()->select('groups', $join, $columns, $where);

    if (isset($group[0])) {

        $url_path = urldecode(Registry::load('config')->url_path);
        $url_path = preg_split('/\//', $url_path);

        if (Registry::load('current_user')->logged_in) {
            if (isset($url_path[1]) && $url_path[1] === 'invitation' || isset($url_path[2]) && $url_path[2] === 'invitation') {
                if (!isset($group[0]['group_member_id']) || empty($group[0]['group_member_id'])) {
                    $invitation_id = $invite_code = 0;

                    if (isset($url_path[2]) && $url_path[2] === 'invitation' && isset($url_path[3]) && isset($url_path[4])) {
                        $invitation_id = $url_path[3];
                        $invite_code = $url_path[4];
                    } else if (isset($url_path[2]) && isset($url_path[3])) {
                        $invitation_id = $url_path[2];
                        $invite_code = $url_path[3];
                    }

                    $invitation_id = filter_var($invitation_id, FILTER_SANITIZE_NUMBER_INT);

                    if (!empty($invitation_id) && !empty($invite_code)) {
                        $columns = $join = $where = null;
                        $columns = ['group_invitations.user_id', 'group_invitations.group_id', 'group_invitations.referrer_user_id'];

                        $where["group_invitations.group_invitation_id"] = $invitation_id;
                        $where["group_invitations.invitation_code"] = $invite_code;
                        $where["group_invitations.expired"] = 0;
                        $where["LIMIT"] = 1;

                        $group_invitation = DB::connect()->select('group_invitations', $columns, $where);

                        if (isset($group_invitation[0])) {
                            if (!isset($group_invitation[0]['user_id']) || empty($group_invitation[0]['user_id'])) {
                                $group_invitation[0]['user_id'] = $user_id;
                            }

                            DB::connect()->update("group_invitations", ["expired" => 1], ["group_invitation_id" => $invitation_id]);

                            $update_site_notification = [
                                'related_group_id' => $group_invitation[0]['group_id'],
                                'notification_type' => 'group_invitation',
                                'user_id' => $group_invitation[0]['user_id'],
                            ];
                            DB::connect()->update("site_notifications", ["related_parameters" => ''], $update_site_notification);

                            include 'fns/add/load.php';

                            $group_member = [
                                'add' => 'group_members',
                                'group_id' => $group_invitation[0]['group_id'],
                                'user_id' => $group_invitation[0]['user_id'],
                                'referrer_user_id' => $group_invitation[0]['referrer_user_id'],
                                'return' => true
                            ];

                            add($group_member, ['force_request' => true]);
                            $group[0]['group_member_id'] = 'recently_joined';
                        }
                    }

                }
            }
        }

        if (empty($group[0]['secret_group']) && empty($group[0]['password'])) {
            if (Registry::load('current_user')->logged_in) {
                if (!isset($group[0]['group_member_id']) || empty($group[0]['group_member_id'])) {

                    if (isset($url_path[1]) && $url_path[1] === 'join' || isset($url_path[2]) && $url_path[2] === 'join') {

                        $referrer_user_id = 0;

                        if (isset($url_path[2]) && $url_path[2] === 'referrer_id') {
                            if (isset($url_path[3]) && !empty($url_path[3])) {
                                $referrer_user_id = $url_path[3];
                            }
                        } else if (isset($url_path[3]) && $url_path[3] === 'referrer_id') {
                            if (isset($url_path[4]) && !empty($url_path[4])) {
                                $referrer_user_id = $url_path[4];
                            }
                        }

                        include 'fns/add/load.php';

                        $group_member = [
                            'add' => 'group_members',
                            'group_id' => $group[0]['group_id'],
                            'user_id' => $user_id,
                            'referrer_user_id' => $referrer_user_id,
                            'return' => true
                        ];

                        add($group_member);
                    }
                }
            }
        } else {
            if (!isset($group[0]['group_member_id']) || empty($group[0]['group_member_id'])) {
                $load_group_conversation = Registry::load('config')->load_group_conversation;
                $skip_meta_tags = true;
                Registry::load('config')->load_group_conversation = null;

                if (isset($_COOKIE['current_group_secret_code']) && !empty($_COOKIE['current_group_secret_code'])) {
                    if ($_COOKIE['current_group_secret_code'] === $group[0]['secret_code']) {
                        $skip_meta_tags = false;
                        Registry::load('config')->load_group_conversation = $load_group_conversation;
                    }
                }
            }
        }

        if (!$skip_meta_tags) {

            $meta_tags['url'] = Registry::load('config')->site_url.$group[0]['slug'].'/';

            if (isset($group[0]['meta_title']) && !empty($group[0]['meta_title'])) {
                $meta_tags['title'] = $group[0]['meta_title'].' - '.Registry::load('settings')->site_name;
            } else {
                $meta_tags['title'] = $group[0]['name'].' - '.Registry::load('settings')->site_name;
            }

            if (isset($group[0]['meta_description']) && !empty($group[0]['meta_description'])) {
                $meta_tags['description'] = $group[0]['meta_description'];
            } else if (isset($group[0]['description']) && !empty($group[0]['description'])) {
                $meta_tags['description'] = $group[0]['description'];
            }

            if (get_image(['from' => 'groups/cover_pics', 'search' => $group_id, 'exists' => true])) {
                $meta_tags['social_share_image'] = get_image(['from' => 'groups/cover_pics', 'search' => $group_id]);
            }
        }
    }
}
?>