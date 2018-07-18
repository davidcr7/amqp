<?php

namespace Bab\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Broker\MessageProvider\PhpAmqpLibMessageProvider;
use Swarrot\Consumer;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Bab\Swarrot\Processor\DumbProcessor;
use Bab\Console\Command\AmqpExt;

class PublishCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('publish')
             ->setDescription('publish some messages')
             ->addArgument('provider', InputArgument::REQUIRED, 'Provider to test [ext|lib]')
             ->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'How many messages to get ?', 1000)
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $provider = $input->getArgument('provider');
        $nbMessages = $input->getOption('messages');

        if ('ext' === $provider) {
            $this->publishWithExt($nbMessages);
        } else {
            $this->publishWithLib($nbMessages);
        }
    }

    /**
     * publishWithExt
     *
     * @param int $nbMessages
     *
     * @return void
     */
    protected function publishWithExt($nbMessages)
    {
        (new Amqp())->publish("message", $nbMessages);
    }

    /**
     * publishWithLib
     *
     * @param int $nbMessages
     *
     * @return void
     */
    protected function publishWithLib($nbMessages)
    {
        $connection = new AMQPConnection('123.206.231.149', 5672, 'guest', 'guest', '/');
        $channel = $connection->channel();

        for ($i = 0; $i < $nbMessages; $i++) {
            $channel->basic_publish(new AMQPMessage("message$i"));
        }

        $channel->close();
        $connection->close();
    }
}
