<?php

namespace AppBundle\Consumer;

class CampaignMailjetConsumer extends AbstractMailerConsumer
{
    protected function getClientId(): string
    {
        return 'app.mailjet.campaign_client';
    }
}
