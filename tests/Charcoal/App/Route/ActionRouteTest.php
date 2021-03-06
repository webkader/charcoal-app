<?php

namespace Charcoal\Tests\App\Route;

// From Pimple
use \Pimple\Container;

// From 'charcoal-app'
use \Charcoal\App\Route\ActionRoute;
use \Charcoal\Tests\App\ContainerProvider;

class ActionRouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tested Class.
     *
     * @var ActionRoute
     */
    private $obj;

    /**
     * Store the service container.
     *
     * @var Container
     */
    private $container;

    /**
     * Set up the test.
     */
    public function setUp()
    {
        $container = $this->container();

        $this->obj = new ActionRoute([
            'logger' => $container['logger'],
            'config' => []
        ]);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(ActionRoute::class, $this->obj);
    }

    /**
     * Set up the service container.
     *
     * @return Container
     */
    private function container()
    {
        if ($this->container === null) {
            $container = new Container();
            $containerProvider = new ContainerProvider();
            $containerProvider->registerLogger($container);

            $this->container = $container;
        }

        return $this->container;
    }
}
