<?php


namespace App\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class UploadConsumer implements ConsumerInterface
{
    private $producer;

    /**
     * @param ProducerInterface $producer
     */
    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;

//        gc_enable();
    }

    /**
     * @var AMQPMessage $msg
     * @return void
     */
    public function execute(AMQPMessage $msg)
    {
        //Process picture upload.
        //$msg will be an instance of `PhpAmqpLib\Message\AMQPMessage` with the $msg->body being the data sent over RabbitMQ.
        $body = $msg->getBody();
        echo $body;

        try {
            if ($body == 'bad') {
                throw new \Exception();
            }

            echo 'Успешно отправлено...'.PHP_EOL;
        } catch (\Exception $exception) {
            echo 'ERROR'.PHP_EOL;
            $this->producer->publish($body);
        }

//        $isUploadSuccess = someUploadPictureMethod();
//        if (!$isUploadSuccess) {
//            // If your image upload failed due to a temporary error you can return false
//            // from your callback so the message will be rejected by the consumer and
//            // requeued by RabbitMQ.
//            // Any other value not equal to false will acknowledge the message and remove it
//            // from the queue
//            return false;
//        }
//        gc_collect_cycles();
    }
}