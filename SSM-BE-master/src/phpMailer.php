<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use \ottimis\phplibs\Logger;

require 'vendor/autoload.php';

function sendMail($toEmail, $subject, $htmlBody, $altBody = '') {

    try {

        $mail = new PHPMailer(true);
        
        //Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        $mail->isSMTP();                                               // Send using SMTP
        $mail->Host       = 'mail.uni.it';                             // Set the SMTP server to send through
        // $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        // $mail->Username   = 'user@example.com';                     // SMTP username
        // $mail->Password   = 'secret';                               // SMTP password
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        // $mail->Port       = 587;                                    // TCP port to connect to
        $fromEmail        = 'noreply@unidata.it';
        $fromName        = 'Auth - Unidata';

        //Recipients
        $mail->setFrom($fromEmail, $fromName);
        // $mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
        $mail->addAddress($toEmail);               // Name is optional
        $mail->addReplyTo($fromEmail, $fromName);
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');

        // Attachments
        // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

        // Content
        $mail->CharSet = "UTF-8";
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody != '' ? $altBody : $htmlBody;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        $logger = new Logger();
        $logger->warning($mail->ErrorInfo, 'M01');
        return false;
    }
}