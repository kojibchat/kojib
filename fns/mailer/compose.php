<?php

$email_addresses = array();
$user_id = 0;
$view_mail = false;
$parameters = array();

if (isset($data['email_addresses'])) {

    if (!is_array($data['email_addresses'])) {
        $data['email_addresses'] = array($data['email_addresses']);
    }
    foreach ($data['email_addresses'] as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_addresses[] = $email;
        }
    }
}

if (isset($data['user_id']) && !empty($data['user_id'])) {
    $user_id = filter_var($data['user_id'], FILTER_SANITIZE_NUMBER_INT);
}

if (empty($email_addresses) && !empty($user_id)) {

    $columns = $where = null;
    $columns = ['site_users.email_address'];
    $where['site_users.user_id'] = $data['user_id'];
    $where['LIMIT'] = 1;
    $user_email_address = DB::connect()->select('site_users', $columns, $where);

    if (isset($user_email_address[0])) {
        $email_addresses[] = $user_email_address[0]['email_address'];
    }
}

if (!empty($email_addresses)) {

    $email_addresses = json_encode($email_addresses);

    if (isset($data['parameters'])) {
        $parameters = json_encode($data['parameters']);
    }

    $insert = [
        'email_addresses' => $email_addresses,
        'user_id' => $user_id,
        'email_category' => $data['category'],
        'email_parameters' => $parameters,
        'created_on' => Registry::load('current_user')->time_stamp,
        'updated_on' => Registry::load('current_user')->time_stamp,
    ];
    DB::connect()->insert('mailbox', $insert);

    if (!DB::connect()->error) {
        $mail_id = DB::connect()->id();

        if (isset($data['view_mail']) && $data['view_mail']) {
            $view_mail = true;
        }

        if (isset($data['send_now']) && $data['send_now'] || $view_mail) {
            mailer('send', ['mail_id' => $mail_id, 'view_mail' => $view_mail]);
        }
    }
}