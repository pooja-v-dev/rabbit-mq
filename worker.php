<?php

error_reporting(-1);
ini_set('display_errors', 'On');
set_error_handler("var_dump");
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/autoload.php';
// require_once __DIR__.'/vendor/php-amqplib/php-amqplib/PhpAmqpLib/Connection/AMQPStreamConnection.php';
// require_once __DIR__.'/vendor/php-amqplib/php-amqplib/PhpAmqpLib/Message/AMQPMessage.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


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

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$send_email = false;

$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    echo " [x] Done\n";
    $mail = new PHPMailer(true); //Argument true in constructor enables exceptions

    $msg->ack();

    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = 'pooja.v@mobiotics.com';                     //SMTP username
    $mail->Password   = 'Pooja@123';                               //SMTP password
    $mail->SMTPSecure = 'tls';         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $mail->Port       = 587;

    //From email address and name
    $mail->From = "pooja.v@mobiotics.com";
    $mail->FromName = "Pooja V";
    $mail->AddAddress("pooja.v@mobiotics.com");

    //Send HTML or Plain Text email
    $mail->isHTML(true);

    $mail->Subject = "Rabbit MQ - Ack";
    $mail->Body = "The messages have been acknowledged by the consumer";

    try {
        $mail->send();
        echo "Message has been sent successfully";
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    }
};
$channel->basic_qos(null, 1, null);

$channel->basic_consume('task_queue', '', false, false, false, false, $callback);

while ($channel->is_open()) {
    $channel->wait();
}
$channel->close();
$connection->close();
