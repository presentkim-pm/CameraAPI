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
use pocketmine\utils\RegistryTrait;


/**
 * Provides {@link ClientboundControlSchemeSetPacket} to simplify player control scheme.
 * Some schemes need to be set to a specific camera before they are applied.
 *
 * @method static ClientboundControlSchemeSetPacket LOCKED_PLAYER_RELATIVE_STRAFE
 * @method static ClientboundControlSchemeSetPacket CAMERA_RELATIVE Required follow_orbit or fixed_boom
 * @method static ClientboundControlSchemeSetPacket CAMERA_RELATIVE_STRAFE Required follow_orbit or fixed_boom
 * @method static ClientboundControlSchemeSetPacket PLAYER_RELATIVE Required fixed_boom
 * @method static ClientboundControlSchemeSetPacket PLAYER_RELATIVE_STRAFE Required fixed_boom
 */
final class ControlSchemePackets{
    use RegistryTrait;

    protected static function setup() : void{
        foreach([
                    "LOCKED_PLAYER_RELATIVE_STRAFE" => ControlScheme::LOCKED_PLAYER_RELATIVE_STRAFE,
                    "CAMERA_RELATIVE" => ControlScheme::CAMERA_RELATIVE,
                    "CAMERA_RELATIVE_STRAFE" => ControlScheme::CAMERA_RELATIVE_STRAFE,
                    "PLAYER_RELATIVE" => ControlScheme::PLAYER_RELATIVE,
                    "PLAYER_RELATIVE_STRAFE" => ControlScheme::PLAYER_RELATIVE_STRAFE,
                ] as $name => $scheme
        ){
            self::_registryRegister($name, ClientboundControlSchemeSetPacket::create($scheme));
        }
    }
}
