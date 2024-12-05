<?php

/*
 * This file is part of the ApiClient package.
 *
 * (c) Alexei Dubrovski <alaxji@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiClient\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Description of StdRoute
 *
 * @author alaxji
 */
class StdLogger extends AbstractLogger implements LoggerInterface
{
    private $intLevel = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7,
    ];
    /**
     * @var LogLevel Уровень логирования
     */
    private $level = null;

    /**
     *
     * @param LogLevel $level
     */
    public function __construct($level = LogLevel::INFO)
    {
        $this->level = $level;
    }

    /**
     *
     * @param type $level
     * @param type $message
     * @param array $context
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->intLevel[$level] <= $this->intLevel[$this->level]) {
            $levelOut = strtoupper($level);
            $line = sprintf("[$levelOut] %s: %s", $message, implode(', ', $context));
            print_r($line . PHP_EOL);
        }
        return $this;
    }

    /**
     * Устанавливает уровень логгирования
     * @param LogLevel $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }
}
