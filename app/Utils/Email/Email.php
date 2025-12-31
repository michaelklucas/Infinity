<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils\Email
 */

namespace App\Utils\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    public static function enviar($destinatario, $assunto, $corpoHtml)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_FROM_EMAIL;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->setFrom(MAIL_FROM_EMAIL, NAME_APP);
            $mail->addAddress($destinatario);
            $mail->isHTML(true);
            $mail->Subject = '=?UTF-8?B?' . base64_encode($assunto) . '?=';
            $mail->Body    = $corpoHtml;
            $mail->AltBody = strip_tags($corpoHtml);
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
            return false;
        }
    }
}