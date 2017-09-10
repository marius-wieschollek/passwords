<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 22:01
 */

namespace OCA\Passwords\Helper;

/**
 * Class PasswordGenerationHelper
 *
 * @package OCA\Passwords\Helper
 */
class PasswordGenerationHelper {

    /**
     * @param int  $strength
     * @param bool $addNumbers
     * @param bool $addSpecialCharacters
     *
     * @param bool $addSmileys
     *
     * @return array
     */
    public function create(
        int $strength = 1,
        bool $addNumbers = false,
        bool $addSpecialCharacters = false,
        bool $addSmileys = false
    ): array {

        $options = $this->getRequestOptions($strength);

        $words    = $this->sendRandomWordRequest($options);
        $password = $this->wordsToPassword($words);

        if(strlen($password) < 12) return $this->create($strength, $addNumbers, $addSpecialCharacters, $addSmileys);

        $amount = $strength == 1 ? 2:$strength;
        if($addNumbers) $password = $this->addNumbers($password, $amount);
        if($addSpecialCharacters) $password = $this->addSpecialCharacters($password, $amount);
        if($addSmileys) $password = $this->addSmileys($password, $amount);

        return [$password, explode(' ', $words)];
    }

    /**
     * @param array $options
     *
     * @return string
     */
    protected function sendRandomWordRequest(array $options): string {
        $request = new HttpRequestHelper();
        $request->setUrl('http://watchout4snakes.com/wo4snakes/Random/RandomPhrase')
                ->setPost($options);

        return $request->sendWithRetry();
    }

    /**
     * @param int $strength
     *
     * @return array
     */
    protected function getRequestOptions(int $strength): array {
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
     * @param string $words
     *
     * @return string
     */
    protected function wordsToPassword(string $words): string {
        return str_replace(' ', '', ucwords($words));
    }

    /**
     * @param string $word
     * @param int    $amount
     *
     * @return string
     */
    protected function addNumbers(string $word, int $amount): string {
        $list = ['e' => '3', 'l' => '1', 'o' => '0', 's' => '5', 't' => '7'];

        return $this->replaceCharacters($word, $amount, $list);
    }

    /**
     * @param string $word
     * @param int    $amount
     *
     * @return string
     */
    protected function addSpecialCharacters(string $word, int $amount): string {
        $list = ['e' => '€', 'a' => '@', 's' => '$', 'i' => '!'];

        return $this->replaceCharacters($word, $amount, $list);
    }

    /**
     * @param string $word
     * @param int    $amount
     *
     * @return string
     */
    protected function addSmileys(string $word, int $amount): string {
        $list = ['d' => ':D', 'p' => ';P', 'o' => ':O'];

        return $this->replaceCharacters($word, $amount, $list);
    }

    /**
     * @param string $word
     * @param int    $amount
     * @param        $list
     *
     * @return string
     */
    protected function replaceCharacters(string $word, int $amount, $list): string {
        $rounds       = 0;
        $replacements = 0;
        $reverse      = false;
        while ($rounds < $amount && $replacements < $amount) {
            foreach ($list as $find => $replace) {
                if(stripos($word, $find) !== false) {
                    if($reverse) {
                        $word    = strrev($word);
                        $replace = strrev($replace);
                    }

                    $word = preg_replace("/$find/i", $replace, $word, 1);
                    if($reverse) $word = strrev($word);
                    $reverse = !false;

                    $replacements++;
                    if($replacements == $amount) break;
                }
            }

            $rounds++;
        }

        return $word;
    }
}