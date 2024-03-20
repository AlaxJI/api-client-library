<?php

/*
 * This file is part of the ApiClient package.
 *
 * (c) Alexei Dubrovski <alaxji@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiClient;

use ApiClient\Request\CurlHandle;
use ApiClient\Request\ParamsBag;
use ApiClient\Helpers\Format;
use ApiClient\Exceptions\ModelException;
use ApiClient\Models\AbstractModel;
use ApiClient\Logger\LoggerManager;
use ApiClient\Logger\StdLogger;
use Psr\Log\LogLevel;

/**
 * Основной класс для получения доступа к моделям
 * При реализации клиента рекомендуется использовать описание `@property type $modelName Описание`
 *
 * @package AbstractClient
 * @version 1.0.0
 * @author Alexei Dubrovski <alaxji@gmail.com>
 * @author dotzero <mail@dotzero.ru>
 */
class AbstractClient
{
    /**
     * @var Fields|null Экземпляр Fields для хранения номеров полей
     * @author dotzero <mail@dotzero.ru>
     */
    public $fields = null;
    /**
     * @var ParamsBag|null Экземпляр ParamsBag для хранения аргументов
     * @author dotzero <mail@dotzero.ru>
     */
    private $parameters = null;
    /**
     * @var CurlHandle Экземпляр CurlHandle для повторного использования
     * @author dotzero <mail@dotzero.ru>
     */
    private $curlHandle;
    /**
     * @var bool Флаг вывода отладочной информации
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    private $debug = false;
    /**
     * @var bool Флаг для использования куков
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    private $cookie = false;
    /**
     * @var object
     */
    private $logger;
    /**
     *
     * @var StdLogger|object
     */
    private $route;

    /**
     * AbstractClient constructor
     *
     * @param Psr\Log\LoggerInterface $logger
     * 
     * @author Alexei Dubrovski <alaxji@gmail.com>
     * @author dotzero <mail@dotzero.ru>
     */
    public function __construct($logger = null)
    {
        $this->parameters = new ParamsBag();
        $this->curlHandle = new CurlHandle();
        $this->logger = new LoggerManager();
        if (is_null($logger)) {
            $this->route = new StdLogger(LogLevel::INFO);
        } else {
            $this->route = $logger;
        }
        $this->logger->addLogger($this->route);
    }

    /**
     * Возвращает экземпляр модели для работы с API
     *
     * @param string $name Название модели
     * @return AbstractModel
     * @throws ModelException
     * @version 1.0.1
     * @author Alexei Dubrovski <alaxji@gmail.com>
     * @author dotzero <mail@dotzero.ru>
     */
    public function __get($name)
    {
        $rClass = new \ReflectionClass($this);
        $namespace = $rClass->getNamespaceName();
        $classname = strtr('\\<namespace>\\Models\\<modelName>', [
            '<namespace>' => $namespace,
            '<modelName>' => Format::upperCamelCase($name),
        ]);
        if (!class_exists($classname)) {
            throw new ModelException('Model not exists: ' . $name);
        }

        // Чистим GET и POST от предыдущих вызовов
        $this->parameters->reset();

        /** @var AbstractModel $item */
        $item = new $classname($this->logger, $this);
        $item->setDebug($this->debug)
            ->setCookies($this->cookie);

        $this->logger->debug("Создан экземпляр класса $name");

        return $item;
    }

    /**
     * Установка флага вывода отладочной информации
     *
     * @param bool $flag Значение флага
     * @return $this
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public function setDebug($flag = true)
    {
        $this->debug = (bool) $flag;

        $level = $this->debug ? LogLevel::DEBUG : LogLevel::INFO;
        $this->logger->setLevel($level);

        return $this;
    }

    /**
     * Установка флага использования cookie
     *
     * @param bool $flag Значение флага
     * @return $this
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public function setCookie($flag = true)
    {
        $this->cookie = (bool) $flag;

        return $this;
    }

    /**
     * Получить ?параметры? клиента
     * @return ParamsBag|null
     */
    public function getParameters(): ?ParamsBag
    {
        return $this->parameters;
    }

    /**
     * Получить обтрботчик cURL
     * @return CurlHandle
     */
    public function getCurlHandle(): CurlHandle
    {
        return $this->curlHandle;
    }
}
