<?php

namespace Tests\AppBundle\Mailer\Message;

use AppBundle\Mailer\Message\CommitteeCitizenInitiativeNotificationMessage;
use AppBundle\Mailer\Message\MessageRecipient;
use Tests\AppBundle\Config;

class CommitteeCitizenInitiativeNotificationMessageTest extends AbstractEventMessageTest
{
    const SHOW_CITIZEN_INITIATIVE_URL = 'https://'.Config::APP_HOST.'/initiative-citoyenne/46ba94dc-b1ff-4807-b8a3-db7366ec805f/2017-08-18-apprenez-a-sauver-des-vies';
    const ATTEND_CITIZEN_INITIATIVE_URL = 'https://'.Config::APP_HOST.'/initiative-citoyenne/46ba94dc-b1ff-4807-b8a3-db7366ec805f/2017-08-18-apprenez-a-sauver-des-vies/inscription';

    public function testCreateCommitteeCitizenInitiativeNotificationMessage()
    {
        $recipients[] = $this->createAdherentMock('em@example.com', 'Émmanuel', 'Macron');
        $recipients[] = $this->createAdherentMock('jb@example.com', 'Jean', 'Berenger');
        $recipients[] = $this->createAdherentMock('ml@example.com', 'Marie', 'Lambert');
        $recipients[] = $this->createAdherentMock('ez@example.com', 'Éric', 'Zitrone');

        $citizenInitiative = $this->createCitizenInitiativeMock(
            'En Marche Lyon',
            '2017-02-01 15:30:00',
            '15 allées Paul Bocuse',
            '69006-69386',
            'en-marche-lyon',
            $this->createAdherentMock('jb@exemple.com', 'Jean', 'Baptiste')
        );
        $citizenInitiative->expects($this->once())->method('getDescription')->willReturn('En Marche à Lyon');

        $message = CommitteeCitizenInitiativeNotificationMessage::create(
            $recipients,
            $this->createCommitteeFeedItemMock(
                $this->createAdherentMock('kl@exemple.com', 'Kévin', 'Lafont'),
                'Cette initiative est superbe !',
                $citizenInitiative
            ),
            self::SHOW_CITIZEN_INITIATIVE_URL,
            self::ATTEND_CITIZEN_INITIATIVE_URL
        );

        $this->assertInstanceOf(CommitteeCitizenInitiativeNotificationMessage::class, $message);
        $this->assertSame('196519', $message->getTemplate());
        $this->assertCount(4, $message->getRecipients());
        $this->assertSame('Des nouvelles de votre comité', $message->getSubject());
        $this->assertCount(7, $message->getVars());
        $this->assertSame(
            [
                'animator_firstname' => 'Kévin',
                'IC_name' => 'En Marche Lyon',
                'IC_description' => 'En Marche à Lyon',
                'IC_date' => 'mercredi 1 février 2017',
                'IC_hour' => '15h30',
                'IC_address' => '15 allées Paul Bocuse, 69006 Lyon 6e',
                'IC_slug' => self::SHOW_CITIZEN_INITIATIVE_URL,
            ],
            $message->getVars()
        );

        $recipient = $message->getRecipient(0);
        $this->assertInstanceOf(MessageRecipient::class, $recipient);
        $this->assertSame('em@example.com', $recipient->getEmailAddress());
        $this->assertSame('Émmanuel Macron', $recipient->getFullName());
        $this->assertSame(
            [
                'animator_firstname' => 'Kévin',
                'IC_name' => 'En Marche Lyon',
                'IC_description' => 'En Marche à Lyon',
                'IC_date' => 'mercredi 1 février 2017',
                'IC_hour' => '15h30',
                'IC_address' => '15 allées Paul Bocuse, 69006 Lyon 6e',
                'IC_slug' => self::SHOW_CITIZEN_INITIATIVE_URL,
                'prenom' => 'Émmanuel',
            ],
            $recipient->getVars()
        );

        $recipient = $message->getRecipient(3);
        $this->assertInstanceOf(MessageRecipient::class, $recipient);
        $this->assertSame('ez@example.com', $recipient->getEmailAddress());
        $this->assertSame('Éric Zitrone', $recipient->getFullName());
        $this->assertSame(
            [
                'animator_firstname' => 'Kévin',
                'IC_name' => 'En Marche Lyon',
                'IC_description' => 'En Marche à Lyon',
                'IC_date' => 'mercredi 1 février 2017',
                'IC_hour' => '15h30',
                'IC_address' => '15 allées Paul Bocuse, 69006 Lyon 6e',
                'IC_slug' => self::SHOW_CITIZEN_INITIATIVE_URL,
                'prenom' => 'Éric',
            ],
            $recipient->getVars()
        );
    }
}