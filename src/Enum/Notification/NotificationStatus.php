<?php

namespace App\Enum\Notification;

enum NotificationStatus: string
{
    case ToSend = 'A envoyer';
    case SendError = "Erreur d'envoi";
    case Sent = 'Envoyé';
}
