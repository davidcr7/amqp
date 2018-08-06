<?php
namespace Dcr\Console\Command;

class AmqpExt{
    
    public $connection;

    public $exchange;

    public $deadLetterExchangeConfig;

    public $exchangeConfig;

    public $channel;

    public $queue;

    public $config;

    public function __construct()
    {
        $this->config = CONFIG;
    
        $this->deadLetterExchangeConfig= [
                                            'name' => "davidcr.test.delay",
                                            'type' => AMQP_EX_TYPE_DIRECT
                                        ];

        $this->deadQueueConfig = [
                                  'name' => "davidcr.delay",
                                  'type' => AMQP_EX_TYPE_DIRECT,
                                  'routing_key' => "davidcr.delay.routing_key",
                                  'property' => []
                                ];

        $this->exchangeConfig = [
                                    'name' => "davidcr.test",
                                    'type' => AMQP_EX_TYPE_DIRECT];

        $this->queueConfig = [
                                'name' => "davidcr.test",
                                'type' => AMQP_EX_TYPE_DIRECT,
                                'routing_key' => "davidcr.routing_key",
                                'property' => [
                                                    'x-dead-letter-exchange' => $this->deadLetterExchangeConfig['name'],
                                                    'x-dead-letter-routing-key' => $this->deadQueueConfig['routing_key'],
                            ]
        ];


        $this->connect();
    }

    public function connect()
    {
        $this->connection = new \AMQPConnection($this->config);
        $this->connection->connect();

    }

    /**
     * 创建信道
     */
    public function setChannle():\AMQPChannel
    {
        return new \AMQPChannel($this->connection);
    }

    /**
     * 创建交换器
     */
    public function setExchange(\AMQPChannel $channel, $exchangeConfig):\AMQPExchange
    {
        $exchange = new \AMQPExchange($channel);
        //交换器名称
        $exchange->setName($exchangeConfig['name']);
        //交换器类型
        $exchange->setType($exchangeConfig['type']);
        //持久化
        $exchange->setFlags(AMQP_DURABLE);
        //声明交换器
        $exchange->declareExchange();

        return $exchange;
    }

    /**
     * 创建队列
     */
    public function setQueue(\AMQPChannel $channle, \AMQPExchange $exchange, $queueConfig):\AMQPQueue
    {
        $queue = new \AMQPQueue($channle);
        //设置队列名
        $queue->setName($queueConfig['name']);
        //设置持久化
        $queue->setFlags(AMQP_DURABLE);
        //设置队列过期时间
        $queue->setArguments($queueConfig['property']);

        //绑定队列
        $queue->bind($exchange->getName(), $queueConfig['routing_key']);

        //声明队列
        $queue->declareQueue();

        return $queue;
    }

    /**
     * 创建延迟队列
     */
    public function delayQueue():\AMQPExchange
    {
        //声明信道
        $channle = $this->setChannle();

        //死信队列
        $deadExchange = $this->setExchange($channle, $this->deadLetterExchangeConfig);
        $this->setQueue($channle, $deadExchange, $this->deadQueueConfig);

        //生产者队列
        $exchange = $this->setExchange($channle, $this->exchangeConfig);
        $this->setQueue($channle, $exchange, $this->queueConfig);
        
        return $exchange;
    }

    /**
     * 发布消息
     */
    public function publish($msg, $nbMessages)
    {
        $exchange = $this->delayQueue();
        $channle = $this->setChannle();
        // $channle->confirmSelect();
        // $channle->setConfirmCallback(function(){
        //     echo "消息发送成功\r\n";
        // },function(){
        //     echo "消息发送失败\r\n";
        // });
        for ($i = 0; $i < $nbMessages; $i++) {
            $exchange->publish($msg.$i, $this->queueConfig['routing_key'], AMQP_DURABLE, ['expiration' => 2000]);

            // if($channle->waitForConfirm()){
            //     echo "ooooooooooooook!";
            // }
        }

        $this->connection->disconnect();
    }

    /**
     * consume方式接收消息
     */
    public function consume()
    {
        $channle = $this->setChannle();
        $exchange = $this->setExchange($channle, $this->deadLetterExchangeConfig);
        $queue = $this->setQueue($channle, $exchange, $this->deadQueueConfig);
        $msg = $queue->consume(function($msg) use ($queue){
            $queue->ack($msg->getDeliveryTag());
            echo date("Y-m-d H:i:s")."---".$msg->getBody().PHP_EOL;
        });
    }

    /**
     * get的方式接收消息
     */
    public function get()
    {
        $channle = $this->setChannle();
        $exchange = $this->setExchange($channle, $this->deadLetterExchangeConfig);
        $queue = $this->setQueue($channle, $exchange, $this->deadQueueConfig);
        $msg = $queue->get(AMQP_AUTOACK);
        echo date("Y-m-d H:i:s")."---".$msg->getBody().PHP_EOL;
    }

    

}