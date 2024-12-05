<?php

/*
 * This file is part of the ApiClient package.
 *
 * (c) Alexei Dubrovski <alaxji@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiClient\Request;

/**
 * Свойства запроса без прямого доступа к ним
 *
 * @author Alexei Dubrovski <alaxji@gmail.com>
 */
class RequestProperties
{
    /**
     * @var bool Флаг использования cookies. По-умолчанию, `false`.
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    protected $cookies = false;
    /**
     * @var bool Флаг вывода отладочной информации. По-умолчанию, `false`.
     * @author dotzero <mail@dotzero.ru>
     */
    protected $debug = false;
    /**
     * @var array Дополнительные параметры в заколовке запроса
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    private $extraHeaders = [];
    /**
     * @var bool Флаг использования authenticated (http basic). Логин и пароль, используемые при соединении, указанные в формате "[username]:[password]". По-умолчанию, `false`.
     * @author Alexei Dubrovski <alaxji@gmail.com>
     * @todo Разобраться зачем он нужен
     */
    protected $httpAuth = false;
    /**
     *
     * @var bool Флаг для обработки ответа от сервера через json_decode(, true). По-умолчанию, `true`.
     * @see parseResponse()
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    protected $parseResponse = true;

    //- Methods
    /**
     * Возвращает список дополнительных http заголовков
     * @return array массив дополнительных http заголовков, либо пустой массив
     */
    protected function getExtraHeaders()
    {
        return $this->extraHeaders;
    }

        /**
     * Копирует свойства запроса в необходимый запрос
     * @param RequestProperties $request
     * @return $this
     */
    public function copyPropertiesTo(RequestProperties &$request)
    {
        $request->setCookies($this->cookies)
            ->setDebug($this->debug)
            ->setHeaders($this->extraHeaders)
            ->setParseResponse($this->parseResponse);

        return $this;
    }

    /**
     * Установка флага использования cookie
     *
     * @param bool $flag Значение флага
     * @return $this
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public function setCookies($flag = true)
    {
        $this->cookies = (bool) $flag;

        return $this;
    }

    /**
     * Установка флага вывода отладочной информации
     *
     * @param bool $flag Значение флага
     * @return $this
     * @author dotzero <mail@dotzero.ru>
     */
    public function setDebug($flag = true)
    {
        $this->debug = (bool) $flag;

        $level = $this->debug ? LogLevel::DEBUG : LogLevel::INFO;
        $this->logger->setLevel($level);

        return $this;
    }

    /**
     * Добавление дополнительных http заголовков, затирает предыдущие
     * @param array $headers массив дополнительных http заголовков
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public function setHeaders($headers = [])
    {
        $this->extraHeaders = $headers;

        return $this;
    }

    /**
     * Установка флага отправки данных через HTTP[S] для cURL.
     *
     * Ищет параметры авторизации в список значений параметров для авторизации по ключам `login` и `password`.
     *
     * @param bool $flag Значение флага
     * @return $this
     * @see ParamsBag::addAuth()
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public function setHttpAuth($flag = true)
    {
        $this->httpAuth = (bool) $flag;

        return $this;
    }

    /**
     * Установка флага обработки ответа от сервера через json_decode(, true).
     *
     * @param bool $flag Значение флага
     * @return $this
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public function setParseResponse($flag = true)
    {
        $this->parseResponse = (bool) $flag;

        return $this;
    }
}
