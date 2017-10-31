<?php

namespace AppBundle\Mailjet\Message;

use AppBundle\Entity\Adherent;
use Ramsey\Uuid\Uuid;

class GroupCreationConfirmationMessage extends MailjetMessage
{
    public static function create(Adherent $adherent, string $city): self
    {
        $message = new self(
            Uuid::uuid4(),
            '54689', // TODO change ID
            $adherent->getEmailAddress(),
            self::fixMailjetParsing($adherent->getFullName()),
            'Votre équipe MOOC est en attente de validation'
        );

        $message->setVar('target_firstname', self::escape($adherent->getFirstName()));
        $message->setVar('group_city', $city);

        return $message;
    }
}
