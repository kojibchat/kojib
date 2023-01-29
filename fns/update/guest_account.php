<?php

if (!empty(Registry::load('current_user')->site_role_attribute)) {
    if (Registry::load('current_user')->site_role_attribute === 'guest_users') {
        if (Registry::load('settings')->allow_guest_users_create_accounts === 'yes') {
            include 'fns/filters/load.php';

            $required_fields = ['email_address', 'password', 'confirm_password'];

            $noerror = true;
            $user_id = Registry::load('current_user')->id;

            $result = array();
            $result['success'] = false;
            $result['error_message'] = Registry::load('strings')->invalid_value;
            $result['error_key'] = 'invalid_value';
            $result['error_variables'] = [];

            if (isset($data['email_address']) && !filter_var($data['email_address'], FILTER_VALIDATE_EMAIL)) {
                $data['email_address'] = null;
            }

            foreach ($required_fields as $required_field) {
                if (!isset($data[$required_field]) || empty($data[$required_field])) {
                    $result['error_variables'][] = [$required_field];
                    $noerror = false;
                }
            }

            if (isset($data['email_address']) && !empty($data['email_address'])) {
                $data['email_address'] = htmlspecialchars(trim($data['email_address']), ENT_QUOTES, 'UTF-8');
                $email_exists = DB::connect()->select('site_users', 'site_users.user_id', ['AND' => ['site_users.email_address' => $data['email_address']], 'site_users.user_id[!]' => $user_id]);

                if (isset($email_exists[0])) {
                    $result['error_variables'] = ['email_address'];
                    $result['error_message'] = Registry::load('strings')->email_exists;
                    $result['error_key'] = 'email_exists';
                    $noerror = false;
                } else if (Registry::load('settings')->email_validator === 'enable' || Registry::load('settings')->email_validator === 'strict_mode') {
                    $email_validator = email_validator($data['email_address']);

                    if (!$email_validator["success"]) {
                        $result['error_variables'] = ['email_address'];
                        $result['error_key'] = 'email_validation_failed';
                        $noerror = false;

                        if ($email_validator["reason"] === "blacklisted") {
                            $result['error_message'] = Registry::load('strings')->email_domain_not_allowed;
                            $result['error_key'] = 'email_domain_blacklisted';
                        } else if ($email_validator["reason"] === "not_whitelisted") {
                            $result['error_message'] = Registry::load('strings')->email_domain_not_allowed;
                            $result['error_key'] = 'email_domain_not_allowed';
                        }

                    }
                }
            }

            if (isset($data['password']) && !empty($data['password'])) {
                if (!isset($data['confirm_password']) || isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
                    $result['error_variables'] = ['password', 'confirm_password'];
                    $result['error_message'] = Registry::load('strings')->password_doesnt_match;
                    $result['error_key'] = 'password_doesnt_match';
                    $noerror = false;
                }
            }

            if ($noerror) {

                $result = array();
                $result['success'] = true;
                $result['todo'] = 'refresh';

                $update_data = [
                    "updated_on" => Registry::load('current_user')->time_stamp
                ];

                if (isset($data['password']) && !empty($data['password'])) {
                    $update_data["encrypt_type"] = 'php_password_hash';
                    $update_data["salt"] = '';
                    $update_data["password"] = password_hash($data['password'], PASSWORD_BCRYPT);
                }

                if (Registry::load('settings')->user_email_verification === 'enable') {
                    $update_data["unverified_email_address"] = $data['email_address'];
                    $verification_code = random_string(['length' => 10]);
                    $update_data["verification_code"] = $verification_code;
                } else {
                    $site_role_id = 1;
                    $default_site_role = DB::connect()->select('site_roles', ['site_roles.site_role_id'], ["site_roles.site_role_attribute" => 'default_site_role']);

                    if (isset($default_site_role[0])) {
                        $site_role_id = $default_site_role[0]['site_role_id'];
                    }

                    $update_data["unverified_email_address"] = null;
                    $update_data["site_role_id"] = $site_role_id;
                    $update_data["email_address"] = $data['email_address'];
                }

                DB::connect()->update("site_users", $update_data, ["user_id" => $user_id]);

                if (Registry::load('settings')->user_email_verification === 'enable') {
                    include('fns/mailer/load.php');

                    $verification_link = Registry::load('config')->site_url.'entry/verify_email_address/'.$user_id.'/'.$verification_code;

                    $mail = array();
                    $mail['email_addresses'] = $data['email_address'];
                    $mail['category'] = 'verification';
                    $mail['user_id'] = $user_id;
                    $mail['parameters'] = ['link' => $verification_link];
                    $mail['send_now'] = true;
                    mailer('compose', $mail);
                    $result['alert_message'] = Registry::load('strings')->confirm_email_address;
                }
            }

        }

    }
}

?>