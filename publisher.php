<?php

require_once __DIR__.'/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

define("RABBITMQ_HOST", "localhost");
define("RABBITMQ_PORT", 5672);
define("RABBITMQ_USERNAME", "guest");
define("RABBITMQ_PASSWORD", "guest");
define("RABBITMQ_QUEUE_NAME", "task_queue");

$connection = new AMQPStreamConnection(
    RABBITMQ_HOST, 
    RABBITMQ_PORT, 
    RABBITMQ_USERNAME, 
    RABBITMQ_PASSWORD
);

$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage(
    $data,
    array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
);

$channel->basic_publish($msg, '', 'task_queue');

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();