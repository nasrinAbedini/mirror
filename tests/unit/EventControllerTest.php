<?php

use PHPUnit\Framework\TestCase;
use Phalcon\Di\FactoryDefault;
use Phalcon\Http\Request;
use Phalcon\Http\Response;

class EventControllerTest extends TestCase
{
    private $di;
    private $controller;

    protected function setUp(): void
    {
        $this->di = new FactoryDefault();
        $this->controller = new \App\Controllers\EventController();
        $this->controller->setDI($this->di);
    }

    public function testSendActionInvalidMethod()
    {
        // Mock Request with GET Method
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('isPost')->willReturn(false);
        $this->di->setShared('request', $mockRequest);

        $response = $this->controller->sendAction();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertStringContainsString('Invalid request method', $response->getContent());
    }

    public function testSendActionMissingMessage()
    {
        // Mock Request with POST Method but empty data
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('isPost')->willReturn(true);
        $mockRequest->method('getJsonRawBody')->willReturn([]);

        $this->di->setShared('request', $mockRequest);

        $response = $this->controller->sendAction();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Message content is required', $response->getContent());
    }

    public function testSendActionSuccess()
    {
        // Mock Request with valid POST data
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('isPost')->willReturn(true);
        $mockRequest->method('getJsonRawBody')->willReturn([
            'users_activities' => ['activity' => 'test']
        ]);
        $this->di->setShared('request', $mockRequest);

        // Mock RabbitMQ Service
        $mockQueue = $this->createMock(\PhpAmqpLib\Channel\AMQPChannel::class);
        $mockQueue->expects($this->once())->method('basic_publish');
        $mockQueue->expects($this->once())->method('close');

        $this->di->setShared('queue', function () use ($mockQueue) {
            $mockQueueService = $this->createMock(\PhpAmqpLib\Connection\AMQPStreamConnection::class);
            $mockQueueService->method('channel')->willReturn($mockQueue);
            return $mockQueueService;
        });

        // Run Action
        $response = $this->controller->sendAction();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Message sent to RabbitMQ successfully', $response->getContent());
    }

    public function testGetInfoActionMissingParameters()
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getQuery')->willReturn(null);

        $this->di->setShared('request', $mockRequest);

        $response = $this->controller->getInfoAction();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Missing required parameters', $response->getContent());
    }

    public function testGetInfoActionSuccess()
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getQuery')->will($this->returnValueMap([
            ['user_id', 'int', 1],
            ['start_time', 'string', '2024-01-01 00:00:00'],
            ['end_time', 'string', '2024-01-01 23:59:59']
        ]));

        $this->di->setShared('request', $mockRequest);

        // Mock ProductivityService
        $mockService = $this->createMock(\App\Services\ProductivityService::class);
        $mockService->method('getProductivityInsights')->willReturn(0.8);

        $response = $this->controller->getInfoAction();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('"focusTimeRatio":0.8', $response->getContent());
    }


}