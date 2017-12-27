<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 13:34
 */

namespace OCA\Passwords\Helper\Words;

use OCA\Passwords\Helper\Http\RequestHelper;

/**
 * Class SnakesWordsHelper
 *
 * @package OCA\Passwords\Helper\Words
 */
class SnakesWordsHelper extends AbstractWordsHelper {

    const SERVICE_URL = 'http://watchout4snakes.com/wo4snakes/Random/RandomPhrase';

    /**
     * @param int $strength
     *
     * @return array
     */
    public function getWords(int $strength): array {

        $options = $this->getServiceOptions($strength);
        $result  = $this->getHttpRequest($options);

        if(empty($result)) return [];

        return explode(' ', $result);
    }

    /**
     * @param array  $options
     *
     * @return mixed
     */
    protected function getHttpRequest(array $options = []) {
        $request = new RequestHelper();
        $request->setUrl(self::SERVICE_URL);

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
    protected function getServiceOptions(int $strength): array {
        $options = [
            'Pos1'   => 'a',
            'Level1' => $strength == 4 ? 50:35,
            'Pos2'   => $strength == 1 ? 'n':'a',
            'Level2' => $strength == 1 ? 35:50,
        ];

        if($strength >= 2) {
            $options['Pos3']   = $strength > 2 ? 'a':'n';
            $options['Level3'] = $strength == 4 ? 60:50;
        }

        if($strength >= 3) {
            $options['Pos4']   = 'n';
            $options['Level4'] = $strength == 4 ? 70:60;
        }

        return $options;
    }
}