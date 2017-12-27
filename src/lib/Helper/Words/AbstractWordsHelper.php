<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 13:20
 */

namespace OCA\Passwords\Helper\Words;

/**
 * Class AbstractWordsHelper
 *
 * @package OCA\Passwords\Helper\Words
 */
abstract class AbstractWordsHelper {

    /**
     * @param int $strength
     *
     * @return array
     */
    abstract public function getWords(int $strength): array;
}