<?php

namespace AppBundle\Consumer;

class TransactionalMailjetConsumer extends AbstractMailerConsumer
{
    protected function getClientId(): string
    {
        return 'app.mailjet.transactional_client';
    }
}
