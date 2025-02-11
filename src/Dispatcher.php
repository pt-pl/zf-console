<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use InvalidArgumentException;
use RuntimeException;
use Zend\Console\Adapter\AdapterInterface as ConsoleAdapter;
use Zend\Console\ColorInterface as Color;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Dispatcher implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Command names mapped to callables
     *
     * @var array
     */
    protected $commandMap = array();

    /**
     * Maps name to callable action
     *
     * @param    string        $command    command name
     * @param    callable    $callable    executable
     * @throws    InvalidArgumentException
     * @return    \ZF\Console\Dispatcher
     */
    public function map($command, $callable)
    {
        if (! is_string($command) || empty($command)) {
            throw new InvalidArgumentException('Invalid command specified; must be a non-empty string');
        }

        if (! is_callable($callable)) {
            if (! is_string($callable) || ! class_exists($callable)) {
                throw new InvalidArgumentException('Invalid command callback specified; must be callable');
            }
        }

        $this->commandMap[$command] = $callable;
        return $this;
    }

    /**
     * Does the dispatcher have a handler for the given command?
     *
     * @param string $command
     * @return bool
     */
    public function has($command)
    {
        return isset($this->commandMap[$command]);
    }

    /**
     * Dispatches route
     *
     * @param Route $route
     * @param ConsoleAdapter $console
     * @throws RuntimeException
     * @return number
     */
    public function dispatch(Route $route, ConsoleAdapter $console)
    {
        $name = $route->getName();
        if (! isset($this->commandMap[$name])) {
            $console->writeLine('');
            $console->writeLine(sprintf('Unhandled command "%s" invoked', $name), Color::WHITE, Color::RED);
            $console->writeLine('');
            $console->writeLine('The command does not have a registered handler.');
            return 1;
        }

        $callable = $this->commandMap[$name];

        if (! is_callable($callable) && is_string($callable)) {
            $callable = new $callable();
            if (! is_callable($callable)) {
                throw new RuntimeException(sprintf(
                    'Invalid command class specified for "%s"; class must be invokable',
                    $name
                ));
            }
            $this->commandMap[$name] = $callable;
        }

        if ($this->getServiceLocator() !== null &&
            $callable instanceof ServiceLocatorAwareInterface &&
            $callable->getServiceLocator() === null) {

            $callable->setServiceLocator($this->getServiceLocator());
        }

        $return = call_user_func($callable, $route, $console);
        return (int) $return;
    }
}
