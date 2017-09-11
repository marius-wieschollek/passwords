<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 11.09.17
 * Time: 21:29
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Helper\Image\GdHelper;
use OCA\Passwords\Helper\Image\ImagickHelper;

/**
 * Class ImageService
 *
 * @package OCA\Passwords\Services
 */
class ImageService {

    /**
     * @return AbstractImageHelper
     */
    public function getImageHelper(): AbstractImageHelper {
        if(class_exists(\Imagick::class) || class_exists(\Gmagick::class)) {
            return new ImagickHelper();
        }

        return new GdHelper();
    }
}