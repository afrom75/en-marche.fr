<?php

namespace AppBundle\Referent;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\ReferentManagedUsersMessage;
use AppBundle\Producer\ReferentMessageDispatcherProducerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class ReferentMessageNotifier
{
    private $manager;
    private $producer;

    public function __construct(
        ObjectManager $manager,
        ReferentMessageDispatcherProducerInterface $producer
    ) {
        $this->manager = $manager;
        $this->producer = $producer;
    }

    public function createMessage(Adherent $referent, ManagedUsersFilter $filter)
    {
        return new ReferentMessage(Uuid::uuid4(), $referent, $filter);
    }

    public function sendMessage(ReferentMessage $message)
    {
        $this->manager->persist(ReferentManagedUsersMessage::createFromMessage($message));
        $this->manager->flush();

        $this->producer->scheduleDispatch($message);
    }
}
