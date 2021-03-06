<?php

namespace Charcoal\App\Route;

// Dependencies from `PHP`
use InvalidArgumentException;

// PSR-7 (http messaging) dependencies
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// Depedencies from `pimple`
use Pimple\Container;

// From `charcoal-config`
use Charcoal\Config\ConfigurableInterface;
use Charcoal\Config\ConfigurableTrait;

// Intra-module (`charcoal-app`) dependencies
use Charcoal\App\Action\ActionInterface;
use Charcoal\App\Route\RouteInterface;
use Charcoal\App\Route\ActionRouteConfig;

/**
 * Action Route Handler.
 */
class ActionRoute implements
    RouteInterface,
    ConfigurableInterface
{
    use ConfigurableTrait;

    /**
     * Create new action route
     *
     * ### Dependencies
     *
     * **Required**
     *
     * - `config` — ScriptRouteConfig
     *
     * **Optional**
     *
     * - `logger` — PSR-3 Logger
     *
     * @param array $data Dependencies.
     */
    public function __construct(array $data)
    {
        $this->setConfig($data['config']);
    }

    /**
     * ConfigurableTrait > createConfig()
     *
     * @param mixed|null $data Optional config data.
     * @return ActionRouteConfig
     */
    public function createConfig($data = null)
    {
        return new ActionRouteConfig($data);
    }

    /**
     * @param Container         $container A container instance.
     * @param RequestInterface  $request   A PSR-7 compatible Request instance.
     * @param ResponseInterface $response  A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function __invoke(Container $container, RequestInterface $request, ResponseInterface $response)
    {
        $config = $this->config();

        $actionController = $config['controller'];

        $action = $container['action/factory']->create($actionController);
        $action->init($request);

        // Set custom data from config.
        $action->setData($config['action_data']);

        // Run (invoke) action.
        return $action($request, $response);
    }
}
