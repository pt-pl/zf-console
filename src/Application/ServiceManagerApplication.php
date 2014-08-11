<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console\Application;

use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use ZF\Console\Application;
use ZF\Console\Dispatcher;

/**
 * Create and execute console applications from single config file and initialize ServiceManager
 */
class ServiceManagerApplication
    extends Application
    implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Initialize the application from single config
     *
     * @param array|Traversable $config Application config
     * @param Console $console Console adapter to use within the application
     * @param Dispatcher $dispatcher Configured dispatcher mapping routes to callables
     */
    public function __construct($config, Console $console, Dispatcher $dispatcher = null)
    {
        if (! is_array($routes) && ! $routes instanceof Traversable) {
            throw new \InvalidArgumentException('Config must be provided as an array or Traversable object');
        }

        if(!isset($config['name']) || !isset($config['version']) || !isset($config['routes']))
        {
            throw new \InvalidArgumentException('Config must contains not empty fields: name, version, routes');
        }

        parent::__construct($config['name'], $config['version'], $config['routes'], $console, $dispatcher);

        if(!empty($config['service_manager'])) {
            $sm = new ServiceManager(new Config($config['service_manager']));
            $sm->setService('ApplicationConfig', $config);

            $this->setServiceLocator($sm);
            $this->getDispatcher()->setServiceLocator($sm);
        }
    }
}
