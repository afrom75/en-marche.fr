<?php

namespace AppBundle\Consumer;

use AppBundle\Entity\ReferentManagedUsersMessage;
use AppBundle\Mailjet\MailjetService;
use AppBundle\Mailjet\Message\ReferentMessage as MailjetMessage;
use AppBundle\Referent\ReferentMessage;
use AppBundle\Repository\Projection\ReferentManagedUserRepository;
use AppBundle\Repository\ReferentManagedUsersMessageRepository;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ReferentMessageDispatcherConsumer extends AbstractConsumer
{
    /**
     * @var MailjetService
     */
    private $mailer;
    /**
     * @var ReferentManagedUsersMessageRepository
     */
    private $referentMessageRepository;
    /**
     * @var ReferentManagedUserRepository
     */
    private $referentManagedUserRepository;

    protected function configureDataConstraints(): array
    {
        return [
            'uuid' => [new Assert\NotBlank()],
        ];
    }

    public function doExecute(array $data): int
    {
        try {
            if (!$referentMessage = $this->referentMessageRepository->findOneByUuid($data['uuid'])) {
                $this->getLogger()->error('Referent message not found', $data);
                $this->writeln($data['uuid'], 'Referent message not found, rejecting');

                return ConsumerInterface::MSG_ACK;
            }

            $referent = $referentMessage->getFrom();
            $message = ReferentMessage::createFromMessage($referentMessage);
            $this->writeln($data['uuid'], 'Dispatching message from '.$referent->getEmailAddress());

            /** @var IterableResult $results */
            $results = $this->referentManagedUserRepository->createDispatcherIterator($referent, $message->getFilter());

            $i = 0;
            $count = 0;
            $chunk = [];

            foreach ($results as $result) {
                ++$i;
                ++$count;
                $chunk[] = $result[0];

                if (MailjetService::PAYLOAD_MAXSIZE === $i) {
                    $this->sendMessage($referentMessage, $message, $chunk, $count);

                    $i = 0;
                    $chunk = [];

                    $this->getManager()->clear();
                }
            }

            if (!empty($chunk)) {
                $this->sendMessage($referentMessage, $message, $chunk, $count);
            }

            return ConsumerInterface::MSG_ACK;
        } catch (\Exception $error) {
            $this->getLogger()->error('Consumer failed', ['exception' => $error]);

            throw $error;
        }
    }

    public function sendMessage(ReferentManagedUsersMessage $savedMessage, ReferentMessage $message, array $recipients, int $count)
    {
        $this->getDoctrine()->getConnection()->beginTransaction();

        try {
            $this->mailer->sendMessage(MailjetMessage::createFromModel($message, $recipients));

            $this->writeln(
                $savedMessage->getUuid()->toString(),
                'Message from '.$message->getFrom()->getEmailAddress().' dispatched ('.$count.')'
            );

            $this->referentMessageRepository->incrementOffset($savedMessage, count($recipients));

            $this->getDoctrine()->getConnection()->commit();
        } catch (\Exception $error) {
            $this->getManager()->close();
            $this->getDoctrine()->getConnection()->rollback();

            throw $error;
        }
    }

    public function setMailer(MailjetService $mailjetService): void
    {
        $this->mailer = $mailjetService;
    }

    public function setReferentManagedUserRepository(ReferentManagedUserRepository $referentManagedUserRepository): void
    {
        $this->referentManagedUserRepository = $referentManagedUserRepository;
    }

    public function setReferentMessageRepository(ReferentManagedUsersMessageRepository $referentMessageRepository): void
    {
        $this->referentMessageRepository = $referentMessageRepository;
    }
}
