<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 13:20
 */

namespace OCA\Passwords\Helper\Words;

use OCA\Passwords\Helper\Http\RequestHelper;

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
    public function getWords(int $strength): array {

        $url     = $this->getWordsUrl();
        $options = $this->getServiceOptions($strength);
        $result  = $this->getHttpRequest($url, $options);

        if(empty($result)) {
            return [];
        }

        return explode(' ', $result);
    }

    /**
     * @param string $url
     * @param array  $options
     *
     * @return mixed
     */
    protected function getHttpRequest(string $url, array $options = []) {
        $request = new RequestHelper();
        $request->setUrl($url);

        if(!empty($options)) {
            $request->setPostData($options);
        }

        return $request->sendWithRetry();
    }

    /**
     * @param int $strength
     *
     * @return array
     */
    abstract protected function getServiceOptions(int $strength): array;

    /**
     * @return string
     */
    abstract protected function getWordsUrl(): string;
}