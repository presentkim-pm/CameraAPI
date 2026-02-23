<?php

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author       PresentKim (debe3721@gmail.com)
 * @link         https://github.com/PresentKim
 * @license      https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\cameraapi\utils;

use pocketmine\block\utils\DyeColor;
use pocketmine\color\Color;

/**
 * Utility class for color space transformations and normalization.
 *
 * This class provides a collection of static-like helper methods to convert
 * and normalize various color objects used within the application.
 * It is designed to be a final, stateless utility.
 */
final class ColorUtils{

    /**
     * Returns the RGB values normalized to a range of 0.0 to 1.0.
     * * This method extracts RGB components from the given color object and
     * scales them from the standard 0-255 integer range to a 0.0-1.0 float range.
     *
     * @param DyeColor|Color $color The color object containing RGB values.
     *
     * @return array{0: float, 1: float, 2: float} An array of [Red, Green, Blue] as floats.
     */
    public static function normalizeRgb(DyeColor|Color $color) : array{
        // If it's a DyeColor instance, unwrap it to get the underlying RGB value object
        if($color instanceof DyeColor){
            $color = $color->getRgbValue();
        }

        // Normalize each channel by dividing by 255.0
        return [
            $color->getR() / 255.0,
            $color->getG() / 255.0,
            $color->getB() / 255.0
        ];
    }

}
