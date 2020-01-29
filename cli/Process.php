<?php
/**
 * Created by PhpStorm.
 * User: Mirel Mitache
 * Date: 19.10.2014
 * Time: 12:25
 */

namespace mpf\cli;


use mpf\base\LogAwareObject;

class Process extends LogAwareObject {

    public $sqlLock = array(
        'table' => 'locks',
        'column_pid' => 'pid',
        'column_command' => 'command'
    );

    public $redisLock = false;

    public $mongoLock = false;

    public $cacheLock = false;

    /**
     * @var static
     */
    private static $_self;

    /**
     * @param array $config
     * @return static
     * @throws \ReflectionException
     */
    public static function get($config = array()): Process
    {
        if (!self::$_self) {
            self::$_self = new static($config);
        }
        return self::$_self;
    }

    public function checkLock() {
        // checks lock for current process.
    }

    public function exec($command, $logFile, $checkLock) {

    }
} 