<?php


namespace App\Traits;


use App\Producer\UploadProducer;
use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @todo Настроить работу брокера
 * Trait RabbitmqTrait
 * @package App\Traits
 */
trait RabbitmqTrait
{
    public function consumeMessage()
    {

    }

    public function produceMessage()
    {
        $connection = new AMQPSSLConnection(
            $this->getParameter('rabbit_mq.default.host'),
            $this->getParameter('rabbit_mq.default.port'),
            $this->getParameter('rabbit_mq.default.user'),
            $this->getParameter('rabbit_mq.default.password'));

        $data = [];
        $uploadProducer = new UploadProducer($connection);
        $msg = array('user_id' => 1235, 'image_path' => '/path/to/new/pic.png');
        $uploadProducer->publish(serialize($msg));
        $uploadProducer->setContentType('application/json');
    }

    public function receiveMessage()
    {
        $connection = new AMQPStreamConnection('172.19.0.1', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $channel->queue_declare('hello', false, false, false, false);

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        $callback = function ($msg) {
            echo ' [x] Received ', $msg->body, "\n";
        };

        $channel->basic_consume('hello', '', false, true, false, false, $callback);


//        while ($channel->is_open()) {
//            $channel->wait();
//        }
    }

    public function sendMessage()
    {
        $connection = new AMQPStreamConnection('172.19.0.1', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $msg = new AMQPMessage('Hello World!');
        $channel->basic_publish($msg, '', 'hello');

        $data = " [x] Sent 'Hello World!'\n";

        $channel->close();
        $connection->close();
    }
}