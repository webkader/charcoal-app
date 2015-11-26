<?php

namespace Charcoal\App\Script;

// Module `charcoal-factory` dependencies
use \Charcoal\Factory\ResolverFactory;

/**
 * The ScriptFactory creates Script (CLI Action) objects
 */
class ScriptFactory extends ResolverFactory
{
    /**
     * @return string
     */
    public function base_class()
    {
        return '\Charcoal\App\Script\ScriptInterface';
    }

    /**
     * @return string
     */
    public function resolver_suffix()
    {
        return 'Script';
    }
}
