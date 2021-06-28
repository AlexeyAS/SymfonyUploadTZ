<?php


namespace App\Producer;

use OldSound\RabbitMqBundle\DependencyInjection\Configuration;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\RegisterPartsPass;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Serializer\Serializer;

class UploadProducer extends Producer
{
    private $serializer;
//
//    public function __construct(Serializer $serializer, AMQPMessage $msgBody) {
//
//    }
}