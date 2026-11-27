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

use pocketmine\network\mcpe\protocol\ClientboundControlSchemeSetPacket;
use pocketmine\network\mcpe\protocol\types\ControlScheme;

/**
 * Provides ClientboundControlSchemeSetPacket to simplify player control scheme.
 * Some schemes need to be set to a specific camera before they are applied.
 */
final class ControlSchemePackets{

    private function __construct(){
        //NOOP
    }

    /**
     * @return ClientboundControlSchemeSetPacket[]
     * @phpstan-return array<string, ClientboundControlSchemeSetPacket>
     */
    public static function getAll() : array{
        return [
            "LOCKED_PLAYER_RELATIVE_STRAFE" => self::LOCKED_PLAYER_RELATIVE_STRAFE(),
            "CAMERA_RELATIVE" => self::CAMERA_RELATIVE(),
            "CAMERA_RELATIVE_STRAFE" => self::CAMERA_RELATIVE_STRAFE(),
            "PLAYER_RELATIVE" => self::PLAYER_RELATIVE(),
            "PLAYER_RELATIVE_STRAFE" => self::PLAYER_RELATIVE_STRAFE()
        ];
    }

    public static function get(string $name) : ClientboundControlSchemeSetPacket{
        return match (strtoupper($name)) {
            "LOCKED_PLAYER_RELATIVE_STRAFE" => self::LOCKED_PLAYER_RELATIVE_STRAFE(),
            "CAMERA_RELATIVE"               => self::CAMERA_RELATIVE(),
            "CAMERA_RELATIVE_STRAFE"        => self::CAMERA_RELATIVE_STRAFE(),
            "PLAYER_RELATIVE"               => self::PLAYER_RELATIVE(),
            "PLAYER_RELATIVE_STRAFE"        => self::PLAYER_RELATIVE_STRAFE(),
            default                         => throw new \InvalidArgumentException("'$name' is invalid control scheme name")
        };
    }

    /**
     * Move relative to the player and follow the facing.
     */
    public static function LOCKED_PLAYER_RELATIVE_STRAFE() : ClientboundControlSchemeSetPacket{
        static $cache = null;
        return $cache ??= ClientboundControlSchemeSetPacket::create(ControlScheme::LOCKED_PLAYER_RELATIVE_STRAFE);
    }

    /**
     * Move relative to the camera, keep the viewing angle horizontal and follow the direction of movement.
     *
     * Required set camera preset to `follow_orbit` or `fixed_boom`
     */
    public static function CAMERA_RELATIVE() : ClientboundControlSchemeSetPacket{
        static $cache = null;
        return $cache ??= ClientboundControlSchemeSetPacket::create(ControlScheme::CAMERA_RELATIVE);
    }

    /**
     * Move relative to the camera, keep the viewing angle horizontal and follow the facing.
     *
     * Required set camera preset to `follow_orbit` or `fixed_boom`
     */
    public static function CAMERA_RELATIVE_STRAFE() : ClientboundControlSchemeSetPacket{
        static $cache = null;
        return $cache ??= ClientboundControlSchemeSetPacket::create(ControlScheme::CAMERA_RELATIVE_STRAFE);
    }

    /**
     * Move relative to the player, keep the viewing angle horizontal and follow the direction of movement.
     *
     * Required set camera preset to `fixed_boom`
     */
    public static function PLAYER_RELATIVE() : ClientboundControlSchemeSetPacket{
        static $cache = null;
        return $cache ??= ClientboundControlSchemeSetPacket::create(ControlScheme::PLAYER_RELATIVE);
    }

    /**
     * Move relative to the player, keep the viewing angle horizontal and follow the facing.
     *
     * Required set camera preset to `fixed_boom`
     */
    public static function PLAYER_RELATIVE_STRAFE() : ClientboundControlSchemeSetPacket{
        static $cache = null;
        return $cache ??= ClientboundControlSchemeSetPacket::create(ControlScheme::PLAYER_RELATIVE_STRAFE);
    }

}
