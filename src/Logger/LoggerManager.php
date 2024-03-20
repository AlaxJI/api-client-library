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
use \SplObjectStorage;

/**
 * Реализацию интерфейса логгирования
 *
 * @author Alexei Dubrovski <alaxji@gmail.com>
 */
class LoggerManager extends AbstractLogger implements LoggerInterface
{
    /**
     * @var SplObjectStorage Список роутов
     */
    private $routes;

    public function __construct()
    {
        $this->routes = new SplObjectStorage();
    }

    /**
     * Добавляет поставщика услуг логгирования
     * @param LoggerInterface $logger
     */
    public function addLogger($logger)
    {
        $this->routes->attach($logger);
    }

    public function log($level, $message, array $context = [])
    {
        foreach ($this->routes as $route) {
            $route->log($level, $message, $context);
        }
    }

    /**
     * Устанавливает уровень логгирования
     * @param LogLevel $level
     */
    public function setLevel($level)
    {
        foreach ($this->routes as $route) {
            if ($route instanceof StdLogger) {
                $route->setLevel($level);
            }
        }
    }
}
