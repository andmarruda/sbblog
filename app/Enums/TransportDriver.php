<?php

namespace App\Enums;

enum TransportDriver: string
{
    case HttpPull = 'http_pull';
    case Webhook = 'webhook';
    case Kafka = 'kafka';
    case Nats = 'nats';
    case Redis = 'redis';
}
