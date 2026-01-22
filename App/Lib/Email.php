<?php
namespace App\Lib;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    public static function enviar($destinatario, $assunto, $mensagemHtml)
    {
        $mail = new PHPMailer(true);

        try {
            
            // Configuração SMTP (ajusta conforme o teu provedor)

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'onbuildoficial@gmail.com'; // <--- teu e-mail
            $mail->Password   = 'jclrmbxrgnobcpyj'; // <--- senha/app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('onbuildoficial@outlook.pt', 'Suporte');
            $mail->addAddress($destinatario);

            $mail->isHTML(true);
            $mail->Subject = mb_convert_encoding($assunto, "UTF-8", "auto");
            $mail->Body    = $mensagemHtml;
            $mail->CharSet = "UTF-8";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar email: {$mail->ErrorInfo}");
            return false;
        }
    }
}
