<?php

namespace Swarrot\Processor\Stack;

use Swarrot\Processor\ProcessorInterface;
use Swarrot\Processor\ConfigurableInterface;
use Swarrot\Processor\InitializableInterface;
use Swarrot\Processor\TerminableInterface;
use Swarrot\Processor\SleepyInterface;
use Swarrot\Broker\Message;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StackedProcessor implements ConfigurableInterface, InitializableInterface, TerminableInterface, SleepyInterface
{
    /**
     * @var mixed
     */
    protected $processor;

    /**
     * @var array
     */
    protected $middlewares;

    /**
     * @param mixed $processor
     * @param array $middlewares
     */
    public function __construct($processor, array $middlewares)
    {
        $this->processor   = $processor;
        $this->middlewares = $middlewares;
    }

    /**
     * setDefaultOptions
     *
     * @param OptionsResolverInterface $resolver
     *
     * @return void
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof ConfigurableInterface) {
                $middleware->setDefaultOptions($resolver);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof InitializableInterface) {
                $middleware->initialize($options);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function process(Message $message, array $options)
    {
        if ($this->processor instanceof ProcessorInterface) {
            return $this->processor->process($message, $options);
        } elseif (is_callable($this->processor)) {
            $processor = $this->processor;

            return $processor($message, $options);
        } else {
            throw new \InvalidArgumentException(
                'Processor MUST implement ProcessorInterface or be a valid callable.'
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function terminate(array $options)
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof TerminableInterface) {
                $middleware->terminate($options);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sleep(array $options)
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof SleepyInterface) {
                if (false === $middleware->sleep($options)) {
                    return false;
                }
            }
        }

        return true;
    }
}
