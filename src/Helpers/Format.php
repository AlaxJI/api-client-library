<?php

/*
 * This file is part of the ApiClient package.
 *
 * (c) Alexei Dubrovski <alaxji@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiClient\Helpers;

/**
 * Хелпер для форматирования данных
 *
 * @package ApiClient\Helpers
 * @version 1.0.0
 * @author dotzero <mail@dotzero.ru>
 * @author Alexei Dubrovski <alaxji@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Format
{

    /**
     * Приведение snake_case к lowerCamelCase
     *
     * @param string $string Строка в `змеином_регистре`
     * @return string Строка `стильВерблюда`
     * @author dotzero <mail@dotzero.ru>
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public static function lowerCamelCase($string)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }

    /**
     * Приведение snake_case к CamelCase
     *
     * @param string $string Строка в `змеином_регистре`
     * @return string Строка `СтильВерблюда`
     * @author dotzero <mail@dotzero.ru>
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public static function upperCamelCase($string)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    /**
     * Приведение CamelCase или сamelCase к snake_case
     *
     * @param string $string Строка в `СтильВерблюда` или `стильВерблюда
     * @return string Строка в `змеином_регистре`
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public static function snakeCase($string)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * Выделить из строки цифры
     *
     * @param string $string
     * @return string
     * @author Alexei Dubrovski <alaxji@gmail.com>
     */
    public static function onlyNumbers($string)
    {
        return preg_replace('/\D/', '', $string);
    }
}
