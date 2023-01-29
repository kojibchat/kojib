<?php

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'firewall']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    $blacklist = '';
    $user_id = 0;

    if (!isset($data['ban_user_id']) && !isset($data['unban_user_id'])) {
        $status = ["enable", "disable"];

        if (!isset($data['status']) || empty($data['status'])) {
            $result['error_variables'][] = ['status'];
            $noerror = false;
        } else if (!in_array($data['status'], $status)) {
            $result['error_variables'][] = ['status'];
            $noerror = false;
        }
    }

    if ($noerror) {

        if (isset($data['ban_user_id'])) {
            $user_id = filter_var($data['ban_user_id'], FILTER_SANITIZE_NUMBER_INT);
        } else if (isset($data['unban_user_id'])) {
            $user_id = filter_var($data['unban_user_id'], FILTER_SANITIZE_NUMBER_INT);
        }

        if (!empty($user_id)) {

            $columns = $where = null;
            $columns = [
                'site_users_device_logs.ip_address',
            ];
            $where["site_users_device_logs.user_id"] = $user_id;
            $user_ip_addresses = DB::connect()->select('site_users_device_logs', $columns, $where);
            $data['blacklist'] = array();

            foreach ($user_ip_addresses as $user_ip_address) {
                $data['blacklist'][] = $user_ip_address['ip_address'];
            }

            if (isset($data['ban_user_id'])) {
                $data['append_ip_address'] = true;
            } else if (isset($data['unban_user_id'])) {

                $ip_blacklist = array();
                include('assets/cache/ip_blacklist.cache');
                $data['blacklist'] = array_diff($ip_blacklist, $data['blacklist']);
            }
        }


        if (isset($data['blacklist']) && !empty($data['blacklist'])) {

            $blacklist = "<?php \n";
            $blacklist .= 'array_push($ip_blacklist,';

            if (is_array($data['blacklist'])) {
                $ip_addresses = $data['blacklist'];
            } else {
                $ip_addresses = preg_split("/\r\n|\n|\r/", $data['blacklist']);
            }

            if (isset($data['append_ip_address']) && $data['append_ip_address']) {
                $ip_blacklist = array();
                include('assets/cache/ip_blacklist.cache');
                $ip_addresses = array_merge($ip_addresses, $ip_blacklist);
            }


            $ip_addresses = array_unique($ip_addresses);

            $total_ip_addresses = count($ip_addresses);
            $ip_index = 1;

            foreach ($ip_addresses as $ip_address) {

                $ip_address = strip_tags($ip_address);

                if (!empty(trim($ip_address))) {
                    $blacklist .= "\n".'"'.addslashes($ip_address).'"';
                    if ($total_ip_addresses !== $ip_index) {
                        $blacklist .= ',';
                    }
                }
                $ip_index = $ip_index+1;
            }

            $blacklist .= "\n);";
        }

        $build = fopen("assets/cache/ip_blacklist.cache", "w");
        fwrite($build, $blacklist);
        fclose($build);

        if (!isset($data['ban_user_id']) && !isset($data['unban_user_id'])) {
            if ($data['status'] !== Registry::load('settings')->firewall) {
                DB::connect()->update("settings", ["value" => $data['status'], "updated_on" => Registry::load('current_user')->time_stamp], ["setting" => 'firewall']);
                cache(['rebuild' => 'settings']);
            }
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'refresh';
    }
}

?>