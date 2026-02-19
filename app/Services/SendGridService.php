<?php

namespace App\Services;

use SendGrid\Mail\Mail;

class SendGridService
{
    public static function send(string $to, string $templateId, array $data)
    {
        $email = new Mail();
        $email->setFrom(
            env('SENDGRID_FROM_EMAIL'),
            env('SENDGRID_FROM_NAME')
        );

        $email->addTo($to);
        $email->setTemplateId($templateId);
        $email->addDynamicTemplateDatas($data);

        $sendgrid = new \SendGrid(env('SENDGRID_API_KEY'));
        $sendgrid->send($email);
    }
}
