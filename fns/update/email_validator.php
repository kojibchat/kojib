<?php

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'email_validator']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    $email_blacklist = $email_whitelist = '';

    $status = ["enable", "disable", "strict_mode"];

    if (!isset($data['status']) || empty($data['status'])) {
        $result['error_variables'][] = ['status'];
        $noerror = false;
    } else if (!in_array($data['status'], $status)) {
        $result['error_variables'][] = ['status'];
        $noerror = false;
    }

    if ($noerror) {

        if (isset($data['email_blacklist']) && !empty($data['email_blacklist'])) {

            $email_blacklist = "<?php \n";
            $email_blacklist .= 'array_push($email_blacklist,';

            if (is_array($data['email_blacklist'])) {
                $blacklists = $data['email_blacklist'];
            } else {
                $blacklists = preg_split("/\r\n|\n|\r/", $data['email_blacklist']);
            }


            $blacklists = array_unique($blacklists);

            $total_domains = count($blacklists);
            $domain_index = 1;

            foreach ($blacklists as $blacklist) {

                $blacklist = strip_tags($blacklist);

                if (!empty(trim($blacklist))) {
                    $email_blacklist .= "\n".'"'.addslashes($blacklist).'"';
                    if ($total_domains !== $domain_index) {
                        $email_blacklist .= ',';
                    }
                }
                $domain_index = $domain_index+1;
            }

            $email_blacklist .= "\n);";

            $build = fopen("assets/cache/email_blacklist.cache", "w");
            fwrite($build, $email_blacklist);
            fclose($build);
        }


        if (isset($data['email_whitelist']) && !empty($data['email_whitelist'])) {

            $email_whitelist = "<?php \n";
            $email_whitelist .= 'array_push($email_whitelist,';

            if (is_array($data['email_whitelist'])) {
                $whitelists = $data['email_whitelist'];
            } else {
                $whitelists = preg_split("/\r\n|\n|\r/", $data['email_whitelist']);
            }


            $whitelists = array_unique($whitelists);

            $total_domains = count($whitelists);
            $domain_index = 1;

            foreach ($whitelists as $whitelist) {

                $whitelist = strip_tags($whitelist);

                if (!empty(trim($whitelist))) {
                    $email_whitelist .= "\n".'"'.addslashes($whitelist).'"';
                    if ($total_domains !== $domain_index) {
                        $email_whitelist .= ',';
                    }
                }
                $domain_index = $domain_index+1;
            }

            $email_whitelist .= "\n);";

            $build = fopen("assets/cache/email_whitelist.cache", "w");
            fwrite($build, $email_whitelist);
            fclose($build);
        }


        if ($data['status'] !== Registry::load('settings')->email_validator) {
            $time_stamp = Registry::load('current_user')->time_stamp;
            DB::connect()->update("settings", ["value" => $data['status'], "updated_on" => $time_stamp], ["setting" => 'email_validator']);
            cache(['rebuild' => 'settings']);
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'refresh';
    }
}

?>