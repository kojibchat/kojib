<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(10);

include 'fns/firewall/load.php';
include 'fns/sql/load.php';
include 'fns/variables/load.php';

if (!role(['permissions' => ['super_privileges' => 'core_settings']])) {
    redirect('404');
}

?>
<!DOCTYPE html>
<html>
<head>
  <title>Validate your Email Settings</title>
<style>
body{
    font-family: sans-serif;
}
form,div.block {
  width: 100%;
  max-width: 500px;
  margin: 100px auto;
  background: #ffffff;
  -webkit-box-shadow: 0px 0px 96px 0px rgba(0,0,0,0.75);
  -moz-box-shadow: 0px 0px 96px 0px rgba(0,0,0,0.75);
  box-shadow: 0px 0px 96px 0px rgb(156 156 156 / 75%);
  padding: 40px;
}

form input {
  width: 100%;
  height: 37px;
  margin-top: 15px;
  padding: 5px;
  font-family: sans-serif;
}
</style>
</head>

<body>
<?php

if (isset($_POST["email"]) && !empty($_POST["email"])) {
    if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        echo "<div class='block'><strong>Error :</strong> Invalid Recipient Email Address</div>";
        exit;
    }

    require 'fns/mailer/php_mailer/Exception.php';
    require 'fns/mailer/php_mailer/PHPMailer.php';
    require 'fns/mailer/php_mailer/SMTP.php';

    $mail = new PHPMailer(true);
    $recipient = $_POST["email"];

    echo "<div class='block'>";
    echo "<strong>PROVIDED INFORMATION : </strong><Br><Br>";
    echo "Email Address : ".Registry::load('settings')->system_email_address."<br>";
    echo "SMTP Host : ".Registry::load('settings')->smtp_host."<br>";
    echo "SMTP Username : ".Registry::load('settings')->smtp_username."<br>";
    echo "SMTP Password : ".Registry::load('settings')->smtp_password."<br>";
    echo "SMTP Port : ".Registry::load('settings')->smtp_port."<br>";
    echo "SMTP Protocol : ".strtoupper(Registry::load('settings')->smtp_protocol)."<br>";
    echo "Recipient : ".$recipient."<br>";
    echo "---------------------------------------<br>";

    try {
        if (Registry::load('settings')->smtp_authentication === 'enable') {
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = Registry::load('settings')->smtp_host;
            $mail->Username = Registry::load('settings')->smtp_username;
            $mail->Password = Registry::load('settings')->smtp_password;
            $mail->Port = Registry::load('settings')->smtp_port;
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;

            if (Registry::load('settings')->smtp_protocol === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        } else {
            echo "<br><strong>Error :</strong> SMTP Authentication is Disabled in Grupo Settings<br>";
        }
        $mail->addAddress($recipient);
        $mail->Subject = 'Test Mail';
        $mail->setFrom(Registry::load('settings')->system_email_address, Registry::load('settings')->sender_name);
        $mail->isHTML(true);
        $mail->Body = 'Hello, This is a Test Message.';
        $mail->send();
    } catch (Exception $e) {
        echo "<br><strong> ERROR LOG :</strong> <br>";
        echo $mail->ErrorInfo;

        if (Registry::load('settings')->smtp_protocol === 'ssl') {
          echo "<br><br><strong> SUGGESTION :</strong> Try with the following details : <br>";
          echo "SMTP Portocol : TLS & SMTP Port : 587 or 25<br>";
        }
    }
    echo "<br><br>---------------------------------------";
    echo "</div>";
} else {
    ?>

<form action="" method="post">
Recipient Email Address : <input type="email" placeholder="Email Address" name="email">
<input type="submit" name="submit" value="Validate">
</form>

<?php
}?>

</body>
</html>
