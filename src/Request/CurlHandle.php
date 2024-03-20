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

use ApiClient\Exceptions\NetworkException;

/**
 * Класс, хранящий повторно используемый обработчик cURL
 *
 * @package ApiClient\Request
 * @version 0.1.0
 * @author dotzero <mail@dotzero.ru>
 * @author Alexei Dubrovski <alaxji@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class CurlHandle
{
    /**
     * @var resource Повторно используемый обработчик cURL
     */
    private $handle;

    /**
     * Закрывает обработчик cURL
     */
    public function __destruct()
    {
        if ($this->handle !== null) {
            @curl_close($this->handle);
        }
    }

    /**
     * Возвращает повторно используемый обработчик cURL или создает новый
     *
     * @return resource
     * @throws NetworkException
     */
    public function open()
    {
        if ($this->handle !== null) {
            return $this->handle;
        }

        if (!function_exists('curl_init')) {
            throw new NetworkException('The cURL PHP extension was not loaded.');
        }
        $this->handle = curl_init();

        return $this->handle;
    }

    /**
     * Сбрасывает настройки обработчика cURL
     * @author Alexei Dubrovski <alaxji@gmail.com>
     * @author dotzero <mail@dotzero.ru>
     */
    public function reset()
    {
        if ($this->handle === null) {
            return;
        }

        curl_setopt($this->handle, CURLOPT_HEADERFUNCTION, null);
        curl_setopt($this->handle, CURLOPT_READFUNCTION, null);
        curl_setopt($this->handle, CURLOPT_WRITEFUNCTION, null);
        curl_setopt($this->handle, CURLOPT_PROGRESSFUNCTION, null);
        curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, null);
        curl_reset($this->handle);
    }
}
