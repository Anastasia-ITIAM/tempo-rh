<?php

namespace App\Service\Admin\Notification;

use App\Enum\Notification\NotificationFlag;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

class MailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig
    ) {
    }

    public function sendMail(NotificationFlag $flag, array $context, string $recipient): void
    {
        $email = new TemplatedEmail()
            ->from($_ENV['MAIL_FROM'])
            ->to($recipient)
            ->subject($flag->getLabel())
            ->html($this->twig->render($flag->getTemplate(), $context));

        $this->mailer->send($email);
    }
}
