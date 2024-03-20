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

use ApiClient\AbstractClient;
use ApiClient\Models\AbstractModel;
use ApiClient\Exceptions\NetworkException;
use ApiClient\Exceptions\ModelException;
use ApiClient\Exceptions\Exception;
use ApiClient\Logger\LoggerManager;
use ApiClient\Request\CurlHandle;
use ApiClient\Request\RequestProperties;
use ApiClient\Request\ParamsBag;
use DateTime;
use Psr\Log\LogLevel;

/**
 * Класс отправляющий запросы к API используя cURL
 *
 * @package ApiClient\Request
 * @version 1.0.1
 * @author dotzero <mail@dotzero.ru>
 * @author Alexei Dubrovski <alaxji@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Request extends RequestProperties
{
    /**
     * @var AbstractClient
     */
    private $client;
    /**
     * @var CurlHandle Экземпляр CurlHandle
     * @author dotzero <mail@dotzero.ru>
     */
    private $curlHandle;
    /**
     * Параметр запроса Endpoint
     * @var string $endpoint
     */
    private $endpoint = null;
    /**
     * @var array|null Дополнительные параметры в заколовке запроса
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    private $extraHeaders = null;
    /**
     * @var int|null Последний полученный HTTP код
     * @author dotzero <mail@dotzero.ru>
     */
    private $lastHttpCode = null;
    /**
     * @var string|null Последний полученный HTTP ответ
     * @author dotzero <mail@dotzero.ru>
     */
    private $lastHttpResponse = null;
    /**
     * @var Logger|null Экземпляр логгера для вывода информации
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    protected $logger = null;
    /**
     * @var ParamsBag|null Экземпляр ParamsBag для хранения аргументов
     * @author dotzero <mail@dotzero.ru>
     */
    private $parameters = null;
    /**
     * @var AbstractModel|null Родительский модуль
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    private $parent;
    /**
     * Включён для совместимости.
     * @var boolean включает/отключает использование параметра CURLOPT_BINARYTRANSFER, для возврата необработанного ответа при использовании константы.
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    private $parseTransfer = true;
    /**
     * @var bool Флаг использования индивидуального Endpoint`а. По-умолчанию, `false`. Endpoint добавляется в конец родительского.
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    private $endpointIndividual = false;

    /**
     * Request constructor
     *
     * @param Logger $logger
     * @param ParamsBag $parameters
     * @param CurlHandle $curlHandle
     * @param AbstractClient $client
     * @param AbstractModel $parent
     * @author Alexei Dubrovski <alaxji@gmail.com>
     * @author dotzero <mail@dotzero.ru>
     */
    public function __construct(LoggerManager &$logger, AbstractClient &$client, AbstractModel &$parent = null)
    {
        if (is_null($client)) {
            throw new ModelException('It mast be the client in there.');
        }

        $this->client = !is_null($client) ? $client : null;
        $this->parent = !is_null($parent) ? $parent : null;
        $this->logger = $logger;

        $parameters = $this->client->getParameters();
        $curlHandle = $this->client->getCurlHandle();
        if (!is_null($this->parent)) {
            $parameters = $this->parent->getParameters();
            $curlHandle = $this->parent->getCurlHandle();
            $this->parent->copyPropertiesTo($this);
        }

        $this->parameters = !is_null($parameters) ? $parameters : new ParamsBag();
        $this->curlHandle = !is_null($curlHandle) ? $curlHandle : new CurlHandle();
    }

    /**
     * Установка флага возврата необработанного ответа при использовании константы.
     *
     * @param bool $flag Значение флага
     * @return $this
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public function setParseTransfer($flag = true)
    {
        $this->parseTransfer = (bool) $flag;

        return $this;
    }

    /**
     * Возвращает последний полученный HTTP код
     *
     * @return int|null
     * @author dotzero <mail@dotzero.ru>
     */
    public function getLastHttpCode()
    {
        return $this->lastHttpCode;
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
     * Возвращает последний полученный HTTP ответ
     *
     * @return null|string
     * @author dotzero <mail@dotzero.ru>
     */
    public function getLastHttpResponse()
    {
        return $this->lastHttpResponse;
    }

    /**
     * Возвращает экземпляр ParamsBag для хранения аргументов
     *
     * @return ParamsBag|null
     * @author dotzero <mail@dotzero.ru>
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Возвращает экземпляр ParamsBag для хранения аргументов
     *
     * @return ParamsBag|null
     * @author dotzero <mail@dotzero.ru>
     */
    protected function getCurlHandle()
    {
        return $this->curlHandle;
    }

    /**
     * Получить точку запроса для модели.
     * @param bool $isFull Если установлено `true` то выводится будет полная точка запроса включая родителей, иначе  конкретная точка зпроса
     * @return string
     */
    public function getEndpoint($isFull = true): string
    {
        $endpoint = '';
        if ($isFull && !$this->isEndpointIndividual() && !is_null($this->getParent())) {
            $endpoint = $this->getParent()->getEndpoint();
        }

        $endpoint .= $this->endpoint;
        return $endpoint;
    }

    /**
     * Выполнить HTTP GET запрос и вернуть тело ответа
     *
     * @param string $url Запрашиваемый URL
     * @param array $parameters Список GET параметров
     * @param null|string $modified Значение заголовка IF-MODIFIED-SINCE
     * @return mixed
     * @throws Exception
     * @throws NetworkException
     * @author dotzero <mail@dotzero.ru>
     */
    protected function get($url, $parameters = [], $modified = null, $debug = null)
    {
        $this->logger->debug('GET method called');

        if (!empty($parameters)) {
            $this->parameters->addGet($parameters);
        }

        return $this->request($url, $modified, $debug);
    }

    /**
     * Выполнить HTTP POST запрос и вернуть тело ответа
     *
     * @param string $url Запрашиваемый URL
     * @param array $parameters Список POST параметров
     * @param null|string $modified Значение заголовка IF-MODIFIED-SINCE
     * @param null|boolean $debug Выводить ответ в отладочной информации. Значение NULL - использовать глобальный параметр debug.
     * @return mixed
     * @throws Exception
     * @throws NetworkException
     * @author dotzero <mail@dotzero.ru>
     */
    protected function post($url, $parameters = [], $modified = null, $debug = null)
    {
        $this->logger->debug('POST method called');

        if (!empty($parameters)) {
            $this->parameters->addPost($parameters);
        }

        return $this->request($url, $modified, $debug);
    }

    /**
     * Выполнить HTTP PUT запрос и вернуть тело ответа
     *
     * @param string $url Запрашиваемый URL
     * @param array $parameters Список POST параметров
     * @param null|string $modified Значение заголовка IF-MODIFIED-SINCE
     * @param null|boolean $debug Выводить ответ в отладочной информации. Значение NULL - использовать глобальный параметр debug.
     * @return mixed
     * @throws Exception
     * @throws NetworkException
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    protected function put($url, $parameters = [], $modified = null, $debug = null)
    {
        $this->logger->debug('PUT method called');

        $ch = $this->getCurlHandle()->open();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        return $this->post($url, $parameters, $modified, $debug);
    }

    /**
     * Подготавливает список заголовков HTTP
     *
     * @param mixed $modified Значение заголовка IF-MODIFIED-SINCE
     * @return array
     * @author dotzero <mail@dotzero.ru>
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    protected function prepareHeaders($modified = null)
    {
        $headers = [];
        $headers[] = 'Connection: keep-alive';
        if ($this->parameters->getJson()) {
            $headers[] = 'Content-Type: application/json';
        }

        if ($modified !== null) {
            if (is_int($modified)) {
                $headers[] = 'IF-MODIFIED-SINCE: ' . $modified;
            } else {
                $headers[] = 'IF-MODIFIED-SINCE: ' . (new DateTime($modified))->format(DateTime::RFC1123);
            }
        }

        if (is_array($this->extraHeaders)) {
            $headers = array_merge($headers, $this->extraHeaders);
        }

        return $headers;
    }

    /**
     * Подготавливает URL для HTTP[S] запроса
     *
     * @param string $url Запрашиваемый URL
     * @return string
     * @author dotzero <mail@dotzero.ru>
     * @author Alexei Dubrovski <alaxji@gmail.com>
     * @throws ModelException
     */
    protected function prepareEndpoint($url)
    {
        $addArray = [];
        if ($this->parameters->getGetAuth()) {
            foreach ($this->parameters->getAuth() as $key => $value) {
                // DELME: удалить в версии 0.1.0
                if ($key === ParamsBag::AUTH_PARAM_DOMAIN) {
                    continue;
                }
                $addArray[$key] = $value;
            }
        }

        $domain = $this->parameters->getDomain();
        $url = $this->getEndpoint() . $url;

        $this->checkEmpty([
            $url,
            $domain,
            ], 'Can`t prepare endpoint');

        $query = http_build_query(array_merge($this->parameters->getGet(), $addArray), null, '&');
        $protocol = $this->parameters->getHttps() ? 'https' : 'http';
        $template = empty($query) ? '%s://%s%s' : '%s://%s%s?%s';
        return sprintf($template, $protocol, $domain, $url, $query);
    }

    /**
     * Выполнить HTTP[S] запрос и вернуть тело ответа
     *
     * @param string $url Запрашиваемый URL (без учёта домена)
     * @param null|string $modified Значение заголовка IF-MODIFIED-SINCE
     * @param null|boolean $debug Выводить ответ в отладочной информации. Значение NULL - использовать глобальный параметр degug.
     * @return mixed
     * @throws Exception
     * @throws NetworkException
     * @author dotzero <mail@dotzero.ru>
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    protected function request($url, $modified = null, $debug = null)
    {
        if (is_null($debug)) {
            $debug = $this->debug;
        }

        $this->logger->debug('json', [$this->parameters->getJson()]);
        $this->logger->debug('cookies', [$this->cookies]);

        $headers = $this->prepareHeaders($modified);
        $endpoint = $this->prepareEndpoint($url);

        $this->logger->debug('url', [$endpoint]);
        $this->logger->debug('headers', $headers);

        $ch = $this->curlHandle->open();

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($this->cookies) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
            curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        }

        if ($this->httpAuth) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->parameters->getAuth('login') . ':' . $this->parameters->getAuth('password'));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_ENCODING, '');

        if ($this->parameters->hasPost()) {
            if ($this->parameters->getJson()) {
                $fields = json_encode($this->parameters->getPost());
            } else {
                $fields = http_build_query($this->parameters->getPost());
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            $this->logger->debug('post params', [$fields]);
        }
        if (!$this->parseTransfer) {
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        }
        if ($this->parameters->hasFile()) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_INFILE, $this->parameters->openFile());
            curl_setopt($ch, CURLOPT_INFILESIZE, $this->parameters->getFileSize());
            $this->logger->debug('file params', $this->parameters->getFileParams());
        }
        if ($this->parameters->hasProxy()) {
            curl_setopt($ch, CURLOPT_PROXY, $this->parameters->getProxy());
        }

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        $this->curlHandle->reset();
        $this->parameters->reset();

        $this->lastHttpCode = $info['http_code'];
        $this->lastHttpResponse = $result;

        if ($debug !== false) {
            $this->logger->debug('curl_exec', [$result]);
        } else {
            $this->logger->debug('curl_exec', 'Set **NOT DEBUG RESULT**');
        }
        $this->logger->debug('curl_getinfo', [var_export($info, true)]);
        $this->logger->debug('curl_error', [$error]);
        $this->logger->debug('curl_errno', [$errno]);

        if ($result === false && !empty($error)) {
            throw new NetworkException($error, $errno);
        }

        if ($this->parseResponse) {
            return $this->parseResponse($result, $info);
        } else {
            return $result;
        }
    }

    /**
     * Парсит HTTP ответ, проверяет на наличие ошибок и возвращает тело ответа
     *
     * @param string $response HTTP ответ
     * @param array $info Результат функции curl_getinfo
     * @return mixed
     * @throws Exception
     * @author dotzero <mail@dotzero.ru>
     */
    protected function parseResponse($response, $info)
    {
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * Хотябы одного из значений. Если есть пустое значение, генерируется ошибка
     * @author Alexei Dubrovski <alaxji@gmail.com>
     * @param array $values
     * @param string $message
     * @throws ModelException
     */
    private function checkEmpty(array $values, string $message)
    {
        foreach ($values as $value) {
            if (empty($value)) {
                throw new ModelException($message);
            }
        }
    }

    /**
     * Получить родительсктий модуль
     * @return AbstractModel|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Получить клиента API
     * @return AbstractClient|null
     */
    public function getClient(): AbstractClient
    {
        return $this->client;
    }

    /**
     * Какой вид точки запроса?
     * 
     *      * `true` - Индивидуальная точка запроса. Родительские точки будут исключены
     *
     * `false` - Общая точка запроса. Родительские точки будут включены
     * @return type
     */
    public function isEndpointIndividual()
    {
        return $this->endpointIndividual;
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
     * Устанавливает в каком виде будет интерпредироваться точка запроса.
     *
     * `true` - Индивидуальная точка запроса. Родительские точки будут исключены
     *
     * `false` - Общая точка запроса. Родительские точки будут включены
     * @param bool $endpointIndividual
     * @return $this
     */
    public function setEndpointIndividual($endpointIndividual = true)
    {
        $this->endpointIndividual = $endpointIndividual;

        return $this;
    }

    /**
     * Устанавливает точку запроса. Устанавливается после домена включая родительские точки запроса, если не установлено обратное
     * @param string $endpoint
     * @return $this
     *
     * @see ApiClient\Request::setEndpointIndividual()
     */
    public function setEndpoint(string $endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }
}
