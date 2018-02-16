<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration\Legacy;

/**
 * Class DecryptionModule
 *
 * This class can be used to decrypt data which was encrypted from the passwords legacy app.
 *
 * @package OCA\Passwords\Legacy
 */
class DecryptionModule {

    /**
     * @param $userId
     * @param $website
     *
     * @return string
     */
    public static function makeKey($userId, $website) {
        $serverKey = \OC::$server->getConfig()->getSystemValue('passwordsalt', '');

        $key = hash_hmac('sha512', $userId, $serverKey);
        $key = hash_hmac('sha512', $key, $website);

        return $key;
    }

    /**
     * @param $hexData
     * @param $key
     *
     * @return bool|string
     * @throws \Exception
     */
    public function decrypt($hexData, $key) {

        $data = hex2bin($hexData);
        if(!$data) throw new \Exception('Failed to convert hex data');

        $salt = substr($data, 0, 128);
        $enc  = substr($data, 128, -64);
        $mac  = substr($data, -64);

        list ($cipherKey, $macKey, $iv) = $this->getKeys($salt, $key);

        if(!$this->hashEquals(hash_hmac('sha512', $enc, $macKey, true), $mac)) {
            throw new \Exception('Calculated hashes do not match');
        }

        $dec  = mcrypt_decrypt(MCRYPT_BLOWFISH, $cipherKey, $enc, MCRYPT_MODE_CBC, $iv);
        $data = $this->unpad($dec);
        if(!$data) throw new \Exception('Failed to decrypt data');

        return $data;
    }

    /**
     * @param $salt
     * @param $key
     *
     * @return array
     * @throws \Exception
     */
    protected function getKeys($salt, $key) {
        $ivSize  = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $keySize = mcrypt_get_key_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);

        if($ivSize === false || $keySize === false) throw new \Exception('Failed to get key size');

        $length = 2 * $keySize + $ivSize;
        $key    = $this->pbkdf2('sha512', $key, $salt, $length);

        if($key === false) throw new \Exception('Failed to get key size');

        $cipherKey = substr($key, 0, $keySize);
        $macKey    = substr($key, $keySize, $keySize);
        $iv        = substr($key, 2 * $keySize);

        return [$cipherKey, $macKey, $iv];
    }

    /**
     * @param $a
     * @param $b
     *
     * @return bool
     */
    function hashEquals($a, $b) {
        $key = mcrypt_create_iv(128, MCRYPT_DEV_URANDOM);

        return hash_hmac('sha512', $a, $key) === hash_hmac('sha512', $b, $key);
    }

    /**
     * @param $algorithm
     * @param $key
     * @param $salt
     * @param $length
     *
     * @return bool|string
     */
    protected function pbkdf2($algorithm, $key, $salt, $length) {
        $size   = strlen(hash($algorithm, '', true));
        $len    = ceil($length / $size);
        $result = '';
        for($i = 1; $i <= $len; $i++) {
            $tmp = hash_hmac($algorithm, $salt.pack('N', $i), $key, true);
            $res = $tmp;
            for($j = 1; $j < 100; $j++) {
                $tmp = hash_hmac($algorithm, $tmp, $key, true);
                $res ^= $tmp;
            }
            $result .= $res;
        }

        return substr($result, 0, $length);
    }

    /**
     * @param $data
     *
     * @return bool|string
     */
    protected function unpad($data) {
        $length = mcrypt_get_block_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $last   = ord($data[ strlen($data) - 1 ]);
        if($last > $length) return false;
        if(substr($data, -1 * $last) !== str_repeat(chr($last), $last)) {
            return false;
        }

        return substr($data, 0, -1 * $last);
    }
}