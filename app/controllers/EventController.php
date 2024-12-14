<?php
declare(strict_types=1);

use Phalcon\Mvc\Controller;
use PhpAmqpLib\Message\AMQPMessage;

class EventController extends Controller
{

    public function sendAction()
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'error' => 'Invalid request method. Only POST is allowed.'
            ])->setStatusCode(405);
        }

        $data = $this->request->getJsonRawBody(true);

        if (!isset($data['users_activities']) || empty($data['users_activities'])) {
            return $this->response->setJsonContent([
                'error' => 'Message content is required.'
            ])->setStatusCode(400);
        }

        try {
            $queueService = $this->getDI()->getShared('queue');
            $channel = $queueService->channel();

            $queueName = $this->di->getConfig()->rabbitmq->queue->eventBatch;

            $channel->queue_declare($queueName, false, true, false, false);


            $jsonData = json_encode($data['users_activities']);

            $msg = new AMQPMessage( $jsonData , [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);

            $channel->basic_publish($msg, '', $queueName);

            $channel->close();

            return $this->response->setJsonContent([
                'message' => 'Message sent to RabbitMQ successfully.'
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            return $this->response->setJsonContent([
                'error' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function getInfoAction()
    {
        $userId = $this->request->getQuery('user_id', 'int');
        $startTime = $this->request->getQuery('start_time', 'string');
        $endTime = $this->request->getQuery('end_time', 'string');


        if (!$userId || !$startTime || !$endTime) {
            return $this->response->setStatusCode(400, 'Bad Request')
                ->setJsonContent(['error' => 'Missing required parameters']);
        }
    
        $focusTimeRatio =  ProductivityService::getProductivityInsights($userId, $startTime, $endTime);
    
        return $this->response->setJsonContent([
            'focusTimeRatio' => $focusTimeRatio
        ]);

    }

    
   
    
}