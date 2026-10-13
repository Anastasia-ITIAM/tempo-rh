<?php

namespace App\Service\Admin\Notification;

use App\Entity\Notification;
use App\Enum\Notification\NotificationFlag;
use App\Enum\Notification\NotificationStatus;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailService $mailService
    ) {
    }

    public function sendNotification(string $recipient, NotificationFlag $flag, array $context): Notification
    {
        $notification = new Notification()
            ->setRecipient($recipient)
            ->setFlag($flag)
            ->setStatus(NotificationStatus::ToSend)
            ->setContext($context);

        $this->em->persist($notification);

        try {
            $this->mailService->sendMail($flag, $context, $recipient);
            $notification->setStatus(NotificationStatus::Sent);
        } catch (\Exception $e) {
            $notification->setStatus(NotificationStatus::SendError);
            $notification->setError($e->getMessage());
        }

        $this->em->flush();

        return $notification;
    }
}
