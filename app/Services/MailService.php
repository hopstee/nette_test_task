<?php

namespace App\Services;

use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;

class MailService
{
    public function __construct()
    {
    }

    public static function sendMail(string $email, string $subject, string $message): void
    {
        $mail = new Message;
        $mail->addTo($email)
            ->setFrom('snikker999@gmail.com', 'Ed')
            ->setSubject($subject)
            ->setBody($message);

//        $mailer = new SendmailMailer;
//        $mailer->send($mail);
    }
}