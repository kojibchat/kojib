<?php


function realtime($data, $private_data = null) {

    $result = array();
    $timeout = false;
    $start_time = time();

    $long_polling_time_out = Registry::load('settings')->request_timeout;

    if (empty($long_polling_time_out)) {
        $long_polling_time_out = 10;
    }

    $set_time_limit = $long_polling_time_out+5;
    $escape = false;
    session_write_close();
    ignore_user_abort(false);
    set_time_limit($set_time_limit);


    if (Registry::load('current_user')->logged_in) {
        if (!isset(Registry::load('current_user')->online_status) || (int)Registry::load('current_user')->online_status !== 1) {
            $update_status = [
                'online_status' => 1,
                "last_seen_on" => get_date(),
                "updated_on" => get_date(),
            ];
            DB::connect()->update('site_users', $update_status, ['user_id' => Registry::load('current_user')->id]);
        }
    }

    while (!$timeout) {
        $timeout = (time() - $start_time) > $long_polling_time_out;

        if ($timeout) {
            break;
        } else {

            if (isset($data["group_id"])) {
                include('fns/realtime/group_messages.php');
            }

            if (Registry::load('current_user')->logged_in) {
                if (isset($data["user_id"])) {
                    include('fns/realtime/private_chat_messages.php');

                    if ($data["user_id"] !== 'all' && isset($data["last_seen_by_recipient"])) {
                        if (role(['permissions' => ['private_conversations' => 'check_read_receipts']])) {
                            include('fns/realtime/last_seen_by_recipient.php');
                        }
                    }
                }

                if (isset($data["unread_group_messages"])) {
                    include('fns/realtime/unread_group_messages.php');
                }

                if (isset($data["unread_private_chat_messages"])) {
                    include('fns/realtime/unread_private_chat_messages.php');
                }

                if (isset($data["unread_site_notifications"])) {
                    include('fns/realtime/unread_site_notifications.php');
                }

                if (isset($data["whos_typing_last_logged_user_id"])) {
                    if (isset($data["group_id"]) && role(['permissions' => ['groups' => 'typing_indicator']])) {
                        include('fns/realtime/whos_typing.php');
                    } else if (isset($data["user_id"]) && role(['permissions' => ['private_conversations' => 'typing_indicator']])) {
                        include('fns/realtime/whos_typing.php');
                    }
                }

                if (isset($data["unresolved_complaints"])) {
                    include('fns/realtime/unresolved_complaints.php');
                }

                if (isset($data["pending_friend_requests"])) {
                    if (Registry::load('settings')->friend_system === 'enable') {
                        include('fns/realtime/pending_friend_requests.php');
                    }
                }

            } else {
                if (isset($data["logged_in_user_id"]) && !empty($data["logged_in_user_id"])) {
                    if ((int)$data["logged_in_user_id"] !== (int)Registry::load('current_user')->id) {
                        $result['reload_page'] = true;
                        $escape = true;
                    }
                }
            }

            if (isset($data["recent_online_user_id"])) {
                include('fns/realtime/online_users.php');
            }

            if (isset($data["last_realtime_log_id"])) {
                include('fns/realtime/realtime_logs.php');
            }

            if ($escape) {
                break;
            }

        }
        sleep(1);
    }

    if ($timeout || $escape) {
        if (isset($data["return"]) && $data["return"]) {
            return $result;
        } else {
            $result = json_encode($result);
            echo $result;
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        die();
    }
}

?>