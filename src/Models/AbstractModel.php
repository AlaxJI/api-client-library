<?php

namespace ApiClient\Models;

use ApiClient\Exceptions\ModelException;
use ApiClient\Helpers\Format;
use ApiClient\Request\Request;
use ArrayAccess;
use ReflectionClass;

/**
 * Class AbstractModel
 *
 * <p>Абстрактный класс для всех моделей</p>
 * <p>При реализации модели рекомендуется использовать описание <code><b>@property</b> type $fieldName Описание</code>.</p>
 * <p>Поля объявляются массивом <code>protected $fields = ['fieldName_1',..., 'fieldName_N']</code></p>
 * <p>Доступ к полям модели может осуществляться, как к массиву. За совпадение типов отвечает пользователь библиотеки.</p>
 * <p>Для каждого поля есть встроенный сеттер и геттер – <code>setИмяПеременной(&lt;значение&gt;)</code>, <code>getИмяПеременной()</code>, которые можно переопределить. Доступ к полям модели через массив или как к полям класса обходит использование сеттеров и геттеров.</p>
 * <p>Значение поля может быть объектом наследующим <b><code>AbstractModel</code></b>. Такая модель будет найдена самостоятельно по правилам: <ul><li><b><code>&lt;ИмяТекушегоКласса&gt;\&lt;ИмяПеременнойВВерблюжемРегистре&gt;</code></b> Если переменная в <code>змеином_регистре</code> она будет преобразована в <code>ВерблюжийРегистр</code> используя как разделитель символ <code>`_`</code></li><li><b><code>&lt;ИмяТекушегоКласса&gt;&lt;ИмяПеременнойВВерблюжемРегистре&gt;</code></b> Если переменная в <code>змеином_регистре</code> она будет преобразована в <code>ВерблюжийРегистр</code> используя как разделитель символ <code>`_`</code></li></ul></p>
 * <p>Получить новый экземпляр объекта наследующего <b><code>AbstractModel</code></b> можно с помощью метода create <code>createИмяПеременной(): ?AbstractModel</code></p>
 * <p>При реализации модели рекомендуется использовать описание <code><b>@method</b> type methodName(type $paramName) Description</code> для всех встроенных методов.</p>
 *
 * @package ApiClient\Models
 * @version 0.2.0
 * @author Alexei Dubrovski <alaxji@gmail.com>
 *
 * @todo Требуется доработка в плане кастомных полей или по типу https://github.com/drillcoder/AmoCRM_Wrap/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class AbstractModel extends Request implements ArrayAccess, ModelInterface
{
    /**
     * @var array Список доступный полей для модели (исключая кастомные поля)
     */
    protected $fields = [];
    /**
     * @var array Список значений полей для модели
     */
    protected $values = [];

    /**
     * Возвращает называние Модели
     *
     * @return mixed
     */
    public function __toString()
    {
        return static::class;
    }

    /**
     * Определяет, существует ли заданное поле модели
     *
     * @link http://php.net/manual/ru/arrayaccess.offsetexists.php
     * @param mixed $offset Название поля для проверки
     * @return boolean Возвращает `true` или `false`
     */
    public function offsetExists($offset)
    {
        return in_array($offset, $this->fields);
    }

    /**
     * Возвращает заданное поле модели
     *
     * @link http://php.net/manual/ru/arrayaccess.offsetget.php
     * @param mixed $offset Название поля для возврата
     * @return mixed Значение поля
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new ModelException('Parametr not exists in ' . get_class($this) . ': ' . $offset);
        }

        $value = isset($this->values[$offset]) ? $this->values[$offset] : $this->tryModel($offset);

        return $value;
    }

    /**
     * Устанавливает заданное поле модели
     *
     * @link http://php.net/manual/ru/arrayaccess.offsetset.php
     * @param mixed $offset Название поля, которому будет присваиваться значение
     * @param mixed $value Значение для присвоения
     */
    public function offsetSet($offset, $value)
    {
        if (!$this->offsetExists($offset)) {
            throw new ModelException('Parametr not exists in ' . get_class($this) . ': ' . $offset);
        }
        $this->values[$offset] = $value;
    }

    /**
     * Удаляет поле модели
     *
     * @link http://php.net/manual/ru/arrayaccess.offsetunset.php
     * @param mixed $offset Название поля для удаления
     */
    public function offsetUnset($offset)
    {
        if (isset($this->values[$offset])) {
            unset($this->values[$offset]);
        }
    }

    /**
     * Получение списка значений полей модели. Если значение поля – значения её полей будет преведено к скалярным типам, массивам и null
     *
     * @return array Список значений полей модели. В качестве значений будет – cкалярные типы и массивы и null. Экземпляры моделей будет преобразованы.
     */
    public function getValues()
    {
        $values = [];
        foreach ($this->values as $key => $value) {
            if (is_a($value, AbstractModel::class)) {
                $value = $value->getValues();
            }
            $values[$key] = $value;
        }

        return $values;
    }

    /**
     *
     * @param type $name
     * @param type $arguments
     * @return type
     * @throws ModelException
     * @todo Кажется, нуждается в оптимизации...
     */
    public function __call($name, $arguments)
    {
        $result = $this;

        $command = substr($name, 0, 3);
        $fieldCamelCase = substr($name, 3);
        if ($fieldCamelCase !== ucfirst($fieldCamelCase)) {
            // NOTE: это не сеттер и геттер... и не new...
            // NOTE: ... может create?
            $command = substr($name, 0, 6);
            $fieldCamelCase = substr($name, 6);
            if ($fieldCamelCase !== ucfirst($fieldCamelCase)) {
                // NOTE: это точно не сеттер и геттер... и не new... и не create
                throw new ModelException('Method `' . $name . '` is not available in' . get_class($this));
            }
        }
        $field = Format::snakeCase($fieldCamelCase);
        if (!$this->offsetExists($field) && $this->offsetExists($fieldCamelCase)) {
            $field = $fieldCamelCase;
        }
        switch ($command) {
            case 'get':
                $result = $this->offsetGet($field);
                break;
            case 'set':
                $this->offsetSet($field, $arguments[0]);
                break;
            case 'new':
            case 'create':
                $result = $this->tryModel($fieldCamelCase);
                break;
            default:
                throw new ModelException('Method `' . $name . '` is not available in' . get_class($this));
        }

        return $result;
    }

    /**
     *
     * @param type $offset
     * @return type
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __isset($name)
    {
        return $this->offsetExists($name) && isset($this->values[$name]);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    /**
     * Пробует получить модель данных
     * @param type $name
     * @throws ModelException
     */
    private function tryModel($name)
    {
        $item = null;

        $rClass = new ReflectionClass($this);
        $fullClassName = $rClass->getName();
        $modelName = Format::upperCamelCase($name);
        $classnames = [
            strtr('\\<full_class_name>\\<model_name>', [
                '<full_class_name>' => $fullClassName,
                '<model_name>' => $modelName,
            ]),
            strtr('\\<full_class_name><model_name>', [
                '<full_class_name>' => $fullClassName,
                '<model_name>' => $modelName,
            ]),
        ];
        foreach ($classnames as $classname) {
            if (class_exists($classname)) {
                // Чистим GET и POST от предыдущих вызовов
                $this->getParameters()->reset();

                /** @var AbstractModel $item */
                $client = $this->getClient();
                $item = new $classname($this->logger, $client, $this);
                $this->copyPropertiesTo($item);

                $this->logger->debug("Создан экземпляр подкласса $name");

                break;
            }
        }

        return $item;
    }
}
