<?php

namespace Charcoal\App\Script;

/**
 *
 */
interface CronScriptInterface
{
    /**
     * @param boolean $use_lock The boolean flag if a lock should be used.
     * @return CronScriptInterface Chainable
     */
    public function set_use_lock($use_lock);

    /**
     * @return boolean
     */
    public function use_lock();

    /**
     * @return boolean
     */
    public function start_lock();

    /**
     * @return void
     */
    public function stop_lock();
}
