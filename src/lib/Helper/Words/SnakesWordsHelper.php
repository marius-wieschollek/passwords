<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 13:34
 */

namespace OCA\Passwords\Helper\Words;

class SnakesWordsHelper extends AbstractWordsHelper {

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

    /**
     * @return string
     */
    protected function getWordsUrl(): string {
        return 'http://watchout4snakes.com/wo4snakes/Random/RandomPhrase';
    }
}