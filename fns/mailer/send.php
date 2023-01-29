<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'fns/mailer/php_mailer/Exception.php';
require 'fns/mailer/php_mailer/PHPMailer.php';
require 'fns/mailer/php_mailer/SMTP.php';
require 'fns/template_engine/latte.php';

$result = array();
$result['success'] = false;
$result['error_log'] = 'invalid_email_address';



$mail = new PHPMailer(true);
$send_mail = false;

try {
    if (Registry::load('settings')->smtp_authentication === 'enable') {
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = Registry::load('settings')->smtp_host;
        $mail->Username = Registry::load('settings')->smtp_username;
        $mail->Password = Registry::load('settings')->smtp_password;
        $mail->Port = Registry::load('settings')->smtp_port;

        if (isset($data['debug']) && $data['debug']) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        } else {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
        }

        if (Registry::load('settings')->smtp_protocol === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
    }

    $mail->setFrom(Registry::load('settings')->system_email_address, Registry::load('settings')->sender_name);
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);

    $template_variables = array();
    $template_variables['logo'] = Registry::load('config')->site_url.'assets/files/logos/email_logo.png';
    $template_variables['site_name'] = Registry::load('settings')->site_name;
    $template_variables['site_slogan'] = Registry::load('settings')->site_slogan;
    $template_variables['email'] = Registry::load('settings')->system_email_address;
    $template_variables['primary_color'] = '#1e8bf1';
    $template_variables['footer_text'] = str_replace('\n', "\n", Registry::load('strings')->mail_footer_text);

    if (!empty($data)) {
        foreach ($data as $key => $value) {
            $mail->$key = $value;
        }
    }

    if (isset($data['mail_id']) && !empty($data['mail_id'])) {
        $columns = $where = null;
        $columns = ['mailbox.email_addresses', 'mailbox.email_category', 'email_parameters'];
        $where['AND'] = ['mailbox.mail_id' => $data['mail_id'], 'send_status' => 0];
        $where['LIMIT'] = 1;
        $mailbox = DB::connect()->select('mailbox', $columns, $where);

        if (isset($mailbox[0])) {

            $email_addresses = json_decode($mailbox[0]['email_addresses']);
            $email_parameters = json_decode($mailbox[0]['email_parameters']);

            foreach ($email_addresses as $email_address) {
                $mail->addAddress($email_address);
            }

            $email_subject = $mailbox[0]['email_category'].'_email_subject';
            $email_heading = $mailbox[0]['email_category'].'_email_heading';
            $email_content = $mailbox[0]['email_category'].'_email_content';
            $email_button_label = $mailbox[0]['email_category'].'_email_button_label';

            $mail->Subject = Registry::load('strings')->$email_subject;

            $template_variables['heading'] = Registry::load('strings')->$email_heading;
            $template_variables['content'] = Registry::load('strings')->$email_content;

            if (isset($email_parameters->append_content)) {
                $template_variables['content'] .= $email_parameters->append_content;
            }

            $template_variables['content'] = str_replace('\n', "\n", $template_variables['content']);
            $template_variables['button_label'] = Registry::load('strings')->$email_button_label;
            $template_variables['button_link'] = $email_parameters->link;
            $send_mail = true;
        }

    } else if (isset($data['send_to']) && !empty($data['send_to']) && filter_var($data['send_to'], FILTER_VALIDATE_EMAIL)) {
        $mail->addAddress($data['send_to']);
        $mail->Subject = $data['subject'];

        $template_variables['heading'] = $data['heading'];
        $template_variables['button_label'] = $data['button']['label'];
        $template_variables['button_link'] = $data['button']['link'];
        $template_variables['content'] = str_replace('\n', "\n", $data['content']);
        $send_mail = true;
    }

    if ($send_mail) {
        $template = new Latte\Engine;
        $mail->Body = $template->renderToString('fns/mailer/template.php', $template_variables);

        if ($todo === 'view' || isset($data['view_mail']) && $data['view_mail']) {
            echo $mail->Body;
        } else {
            $mail->send();
            if (isset($mailbox[0])) {
                DB::connect()->update('mailbox', ['send_status' => 1], ['mail_id' => $data['mail_id']]);
            }
        }
        $result = array();
        $result['success'] = true;
    }
} catch (Exception $e) {

    if (isset($mailbox[0])) {
        DB::connect()->update('mailbox', ['send_status' => 2, 'mail_error_log' => $mail->ErrorInfo], ['mail_id' => $data['mail_id']]);
    }

    $result = array();
    $result['success'] = false;
    $result['error_log'] = $mail->ErrorInfo;
}

if (isset($data["print"]) && $data["print"]) {
    $result = json_encode($result);
    echo $result;
} else {
    return $result;
}