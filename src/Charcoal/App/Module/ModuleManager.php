<?php

namespace Charcoal\App\Module;

// PSR-3 (logger) dependencies
use \Psr\Log\LoggerAwareInterface;
use \Psr\Log\LoggerAwareTrait;

// Module `charcoal-factory` dependencies
use \Charcoal\Factory\FactoryInterface;

// Module `charcoal-config` dependencies
use \Charcoal\Config\ConfigurableInterface;
use \Charcoal\Config\ConfigurableTrait;

// Local namespace dependencies
use \Charcoal\App\AppAwareInterface;
use \Charcoal\App\AppAwareTrait;

/**
 * q
 */
class ModuleManager implements
    AppAwareInterface,
    ConfigurableInterface,
    LoggerAwareInterface
{
    use AppAwareTrait;
    use ConfigurableTrait;
    use LoggerAwareTrait;

    /**
     * @var array $modules
     */
    private $modules = [];

    /**
     * @var FactoryInterface $moduleFactory
     */
    private $moduleFactory;

    /**
     * Manager constructor
     *
     * @param array $data The dependencies container.
     */
    public function __construct(array $data)
    {
        $this->setLogger($data['logger']);
        $this->setConfig($data['config']);
        $this->setApp($data['app']);
        $this->setModuleFactory($data['module_factory']);
    }

    /**
     * @param FactoryInterface $factory The Module Factory to create module instances.
     * @return ModuleManager Chainable
     */
    protected function setModuleFactory(FactoryInterface $factory)
    {
        $this->moduleFactory = $factory;
        return $this;
    }

    /**
     * @return FactoryInterface
     */
    protected function moduleFactory()
    {
        return $this->moduleFactory;
    }

    /**
     * @param array $modules The list of modules to add.
     * @return ModuleManager Chainable
     */
    public function setModules(array $modules)
    {
        foreach ($modules as $moduleIdent => $moduleConfig) {
            $this->addModule($moduleIdent, $moduleConfig);
        }
        return $this;
    }

    /**
     * @param string                $moduleIdent  The module identifier.
     * @param array|ConfigInterface $moduleConfig The module configuration data.
     * @return ModuleManager Chainable
     */
    public function addModule($moduleIdent, array $moduleConfig)
    {
        $this->modules[$moduleIdent] = $moduleConfig;
        return $this;
    }

    /**
     * @return void
     */
    public function setupModules()
    {
        $modules = $this->config();

        foreach ($modules as $moduleIdent => $moduleConfig) {
            if ($moduleConfig === false || (isset($moduleConfig['active']) && !$moduleConfig['active'])) {
                continue;
            }

            $module = $this->moduleFactory()->create($moduleIdent);
            $module->setup();
        }
    }
}
