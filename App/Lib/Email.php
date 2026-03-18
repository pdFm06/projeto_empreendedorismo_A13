<?php

namespace App\Lib;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    public static function enviar($destinatario, $assunto, $mensagemHtml)
    {
        $configPath = __DIR__ . '/../Config/mail.php';

        if (!file_exists($configPath)) {
            return false;
        }

        $config = require $configPath;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['port'];

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($destinatario);

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $mensagemHtml;

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
}