<?php
namespace Dcr\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Broker\MessageProvider\PhpAmqpLibMessageProvider;
use Swarrot\Consumer;
use PhpAmqpLib\Connection\AMQPConnection;
use Dcr\Swarrot\Processor\DumbProcessor;

class GetCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('get')
            ->setDescription('Try to get & ack some messages')
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

        if ('ext' === $provider) {
            (new AmqpExt())->consume();
            // (new AmqpExt())->get();
        } else {
            
        }

        $stack = (new \Swarrot\Processor\Stack\Builder())
            ->push('Swarrot\Processor\MaxMessages\MaxMessagesProcessor')
            ->push('Swarrot\Processor\Ack\AckProcessor', $messageProvider)
        ;
        $processor = $stack->resolve(new DumbProcessor());

        $consumer = new Consumer($messageProvider, $processor);
        $consumer->consume(['max_messages' => (int) $input->getOption('messages')]);
    }
}
