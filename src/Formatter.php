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

/**
 * Description of Formatter
 *
 * @author alaxji
 */
class Formatter
{
    const MONTH_LIST = array(
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сенятбря',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря'
    );
    const NUMBER_LIST = array(
        1 => 'первого',
        2 => 'второго',
        3 => 'третьего',
        4 => 'четвёртого',
        5 => 'пятого',
        6 => 'шестого',
        7 => 'сетьмого',
        8 => 'восемого',
        9 => 'девятого',
        10 => 'десятого',
        11 => 'одиннадцатого',
        12 => 'двенадцатого',
        13 => 'тринадцатого',
        14 => 'четырнадцатого',
        15 => 'пятнадцатого',
        16 => 'шестнадцатого',
        17 => 'семнадцатого',
        18 => 'восемнадцатого',
        19 => 'девятнадцатого',
        20 => 'двадцатого',
        30 => 'тридцатого',
    );
    const NUMBER_DOWN_LIST = array(
        2 => 'двадцать',
        3 => 'тридцать'
    );

    /**
     *
     * @param int $day День месяца
     * @param int $month - Номер месяца
     */
    public static function date2text($day, $month)
    {
        $result = '';
        if ($day <= 20 || $day == 30) {
            $result .= self::NUMBER_LIST[$day];
        } else {
            $num1 = (int) (floor($day / 10));
            $num2 = (int) ($day % 10);
            $result .= self::NUMBER_DOWN_LIST[$num1];
            $result .= ' ' . self::NUMBER_LIST[$num2];
        }

        $result .= ' ' . self::MONTH_LIST[$month];
        return $result;
    }
}
