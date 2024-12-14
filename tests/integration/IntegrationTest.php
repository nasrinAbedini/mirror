<?php
use PHPUnit\Framework\TestCase;
use Phalcon\Di\FactoryDefault;
use Phalcon\Di\Di;


class IntegrationTest extends TestCase
{
    private $di;

    private bool $loaded = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->di = new FactoryDefault();

        Di::reset();
        Di::setDefault($this->di);

        $this->loaded = true;
    }


    public function testDatabaseAndRabbitMqConnections()
    {
        try {
            
            $db = $this->di->get('db');
            $result = $db->fetchOne('SELECT 1');
            $this->assertEquals(1, $result[0], 'Database connection failed');
        } catch (\Exception $e) {
            $this->fail('Database connection failed: ' . $e->getMessage());
        }

        try {
            $queueService = $this->di->get('queue');
            $channel = $queueService->channel();

            $queueName = 'test_queue';
            $channel->queue_declare($queueName, false, true, false, false);

            $testMessage = 'Hello RabbitMQ';
            $msg = new \PhpAmqpLib\Message\AMQPMessage($testMessage);
            $channel->basic_publish($msg, '', $queueName);

            
            $channel->basic_consume($queueName, '', false, true, false, false, function ($msg) use ($testMessage) {
                $this->assertEquals($testMessage, $msg->body, 'RabbitMQ message mismatch');
            });

            $channel->wait(null, false, 5); 

            $channel->close();
            $queueService->close();
        } catch (\Exception $e) {
            $this->fail('RabbitMQ connection failed: ' . $e->getMessage());
        }
    }
}