<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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

        for($i = 0; $i < 24; $i++) {
            $result = trim($this->getHttpRequest($options));
            if(empty($result)) continue;

            $words = explode(' ', $result);

            if($this->isWordsArrayValid($words)) return $words;
        }

        return [];
    }

    /**
     * @param array $options
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
            'Level1' => $strength == 1 ? 35:20,
            'Pos2'   => $strength == 1 ? 'n':'a',
            'Level2' => $strength == 1 ? 50:30,
        ];

        if($strength > 1) {
            $options['Pos3']   = $strength == 2 ? 'n':'a';
            $options['Level3'] = $strength == 2 ? 50:40;
        }

        if($strength > 2) {
            $options['Pos4']   = $strength == 3 ? 'n':'a';
            $options['Level4'] = 50;
        }

        if($strength == 4) {
            $options['Pos5']   = 'n';
            $options['Level5'] = 60;
        }

        return $options;
    }
}