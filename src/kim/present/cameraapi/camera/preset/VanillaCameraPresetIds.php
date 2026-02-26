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

namespace kim\present\cameraapi\camera\preset;

/**
 * Lists the fixed sequential IDs for vanilla camera presets.
 *
 * These IDs are strictly defined by the Minecraft client's registration order.
 * Any custom presets registered via {@see CameraPresetRegistry} will start from ID 6 and onwards.
 */
final class VanillaCameraPresetIds{

    /** @var int Internal index for "minecraft:first_person" */
    public const FIRST_PERSON = 0;

    /** @var int Internal index for "minecraft:fixed_boom" */
    public const FIXED_BOOM = 1;

    /** @var int Internal index for "minecraft:follow_orbit" */
    public const FOLLOW_ORBIT = 2;

    /** @var int Internal index for "minecraft:free" */
    public const FREE = 3;

    /** @var int Internal index for "minecraft:third_person" */
    public const THIRD_PERSON = 4;

    /** @var int Internal index for "minecraft:third_person_front" */
    public const THIRD_PERSON_FRONT = 5;

    private function __construct(){
        // NOOP
    }

}
