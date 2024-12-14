<?php
namespace App\Tasks;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Phalcon\Cli\Task;

class WorkerTask extends Task
{
    public function mainAction()
    {
        try {
            $queueService = $this->getDI()->getShared('queue');
            $channel = $queueService->channel();
            $config = $this->getDI()->getConfig()->rabbitmq;
            $queueName = $config->queue->eventBatch;

            $channel->queue_declare($queueName, false, true, false, false);

            echo " [*] Waiting for messages in queue: $queueName. To exit press CTRL+C\n";

            $callback = function (AMQPMessage $msg) {
                echo " [x] Received: ", $msg->body, "\n";

                $this->processMessage(json_decode($msg->body, true));

                echo " [x] Done\n";

                $msg->ack();
            };

            $channel->basic_consume($queueName, '', false, false, false, false, $callback);

            while ($channel->is_consuming()) {
                $channel->wait();
            }

            $channel->close();
            $queueService->close();
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    private function processMessage(array $data): void
    {
        try {
            $db = $this->getDI()->getShared('db');

            foreach ($data as $userData) {
                if (!isset($userData['user_id']) || !isset($userData['activities'])) {
                    echo " [!] Missing user_id or activities for a user\n";
                    continue;
                }

                $userId = $userData['user_id'];
                $activities = $userData['activities'];

                foreach ($activities as $activity) {
                    try {
                        $requiredFields = ['activity_type', 'activity_duration', 'start_time', 'end_time', 'priority_level', 'description'];
                        foreach ($requiredFields as $field) {
                            if (!isset($activity[$field])) {
                                throw new \InvalidArgumentException("Missing required field: $field");
                            }
                        }
                       
                        $db->insert(
                            'user_activities',
                            [
                                $activity['activity_type'],
                                $activity['activity_duration'],
                                date('Y-m-d H:i:s', strtotime($activity['start_time'])),
                                date('Y-m-d H:i:s', strtotime($activity['end_time'])),
                                $activity['priority_level'],
                                $activity['description'],
                                $userId
                            ],
                            [
                                'activity_type',
                                'activity_duration',
                                'start_time',
                                'end_time',
                                'priority_level',
                                'description',
                                'user_id'
                            ]
                        );

                        echo " [x] Activity stored successfully for user ID {$userId}\n";
                    } catch (\Exception $e) {
                        echo " [!] Error storing activity for user ID {$userId}: ", $e->getMessage(), "\n";
                        continue;
                    }
                }
            }

            echo " [x] All activities processed\n";
        } catch (\Exception $e) {
            echo " [!] Error processing message: ", $e->getMessage(), "\n";
        }
    }
}