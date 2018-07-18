<?php
namespace Swarrot\Processor\RPC;

use Psr\Log\LoggerInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;
use Swarrot\Processor\ConfigurableInterface;
use Swarrot\Processor\SleepyInterface;

/**
 * Act as a RPC client that waits for a certain message to terminate
 *
 * This processor is a leaf processor ; other processors cannot be nested under
 * this processor.
 *
 * It waits for a certain message (with a proper `correlation_id`) to tell the
 * consumer that the message was processed, and that the consumer should be
 * killed afterwards.
 *
 * @author Baptiste Clavié <clavie.b@gmail.com>
 */
class RpcClientProcessor implements ProcessorInterface, ConfigurableInterface, SleepyInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ProcessorInterface */
    private $processor;

    /** @var boolean */
    private $awoken = false;

    public function __construct(ProcessorInterface $processor = null, LoggerInterface $logger = null)
    {
        $this->processor = $processor;
        $this->logger = $logger;
    }

    /** {@inheritDoc} */
    public function process(Message $message, array $options)
    {
        $properties = $message->getProperties();

        // check for invalid correlation_id properties (not set, or invalid)
        if (!isset($properties['correlation_id'])) {
            return;
        }

        if ($properties['correlation_id'] !== $options['rpc_client_correlation_id']) {
            return;
        }

        $result = null;

        $this->logger and $this->logger->info('Message received from the RPC Server ; terminating consumer', ['correlation_id' => $properties['correlation_id']]);
        $this->awoken = true;

        if (null !== $this->processor) {
            $this->logger and $this->logger->info('Sending message to sub-processor');
            $result = $this->processor->process($message, $options);
        }

        return $result;
    }

    /** {@inheritDoc} */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['rpc_client_correlation_id']);
    }

    /** {@inheritDoc} */
    public function sleep(array $options)
    {
        return !$this->awoken;
    }
}

