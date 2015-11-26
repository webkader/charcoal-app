<?php

namespace Charcoal\App\Script;

use \Exception;

/**
*
*/
trait CronScriptTrait
{
    /**
     * @var boolean $use_lock
     */
    private $use_lock = false;

    /**
     * Lock file pointer
     * @var resource $lock_fp
     */
    private $lock_fp;
    

    /**
     * @param boolean $use_lock The boolean flag if a lock should be used.
     * @return CronScriptInterface Chainable
     */
    public function set_use_lock($use_lock)
    {
        $this->use_lock = !!$use_lock;
        return $this;
    }

    /**
     * @return boolean
     */
    public function use_lock()
    {
        return $this->use_lock;
    }

    /**
     * @throws Exception If the lock file can not be opened.
     * @return boolean
     */
    public function start_lock()
    {
        $lock_name = str_replace('\\', '-', get_class($this));
        $lock_file = sys_get_temp_dir().'/'.$lock_name;
        $this->lock_fp = fopen($lock_file, 'w');
        if (!$this->lock_fp) {
             throw new Exception(
                 'Can not run action. Lock file not available.'
             );
        }
        if (flock($this->lock_fp, LOCK_EX)) {
            return true;
        } else {
            throw new Exception(
                'Can not run action. Lock file not available.'
            );
        }
    }

    /**
     * @return void
     */
    public function stop_lock()
    {
        if ($this->lock_fp) {
            flock($this->lock_fp, LOCK_UN);
            fclose($this->lock_fp);
        }
    }
}
