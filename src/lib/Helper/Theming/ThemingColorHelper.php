<?php
/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\Theming;

class ThemingColorHelper {

    const string COLOR_BLACK       = '#000000';
    const string COLOR_WHITE       = '#ffffff';
    const int    MAX_CHANNEL_VALUE = 255;

    /** sRGB transfer function, per WCAG 2.x. The threshold is the older sRGB value,
     *  which WCAG kept for consistency — do not change it to 0.04045. */
    const float SRGB_LINEAR_SEGMENT_THRESHOLD = 0.03928;
    const float SRGB_LINEAR_SEGMENT_SLOPE     = 12.92;
    const float SRGB_GAMMA_OFFSET             = 0.055;
    const float SRGB_GAMMA_EXPONENT           = 2.4;

    /** Rec. 709 luma coefficients, keyed by their offset in a 6-digit hex string. */
    const array LUMINANCE_CHANNEL_WEIGHTS = [0 => 0.2126, 2 => 0.7152, 4 => 0.0722];

    const float WHITE_RELATIVE_LUMINANCE = 1.0;
    const float CONTRAST_AMBIENT_FLARE   = 0.05;
    const float CONTRAST_RATIO_THRESHOLD = 4.5;

    public function getTextColor(string $color): string {
        $hex = ltrim(trim($color), '#');

        if(strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if(strlen($hex) !== 6) {
            return self::COLOR_BLACK;
        }

        $luminance = 0;
        foreach(self::LUMINANCE_CHANNEL_WEIGHTS as $offset => $weight) {
            $channel   = hexdec(substr($hex, $offset, 2)) / self::MAX_CHANNEL_VALUE;
            $channel   = $channel <= self::SRGB_LINEAR_SEGMENT_THRESHOLD ?
                $channel / self::SRGB_LINEAR_SEGMENT_SLOPE:
                pow(($channel + self::SRGB_GAMMA_OFFSET) / (1 + self::SRGB_GAMMA_OFFSET), self::SRGB_GAMMA_EXPONENT);
            $luminance += $channel * $weight;
        }

        return (self::WHITE_RELATIVE_LUMINANCE + self::CONTRAST_AMBIENT_FLARE) / ($luminance + self::CONTRAST_AMBIENT_FLARE) <
               self::CONTRAST_RATIO_THRESHOLD ? ThemingColorHelper::COLOR_BLACK:ThemingColorHelper::COLOR_WHITE;
    }
}