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

namespace kim\present\cameraapi;

use kim\present\cameraapi\session\CameraSession;
use kim\present\cameraapi\session\CameraSessionManager;
use kim\present\cameraapi\timeline\CameraTimeline;
use pocketmine\player\Player;

/**
 * Main entry point for the CameraAPI plugin.
 *
 * This class provides a static method `of()` to easily access the {@link CameraSession} for a given player.
 * It simplifies the API usage by abstracting away the session management logic.
 *
 * @see Camera::of()
 */
final class Camera{

    /**
     * Access the camera API session for a specific player.
     *
     * This is the main entry point for the CameraAPI plugin.
     * It retrieves the existing session or creates a new one if necessary.
     *
     * Example usage:
     * Camera::of($player)->fade()->in(1.0)->send();
     *
     * @param Player $player The target player.
     *
     * @return CameraSession The session instance for controlling the player's camera.
     */
    public static function of(Player $player) : CameraSession{
        $session = CameraSessionManager::getSession($player);
        if($session === null){
            return CameraSessionManager::createSession($player);
        }
        return $session;
    }

    /**
     * Creates a new empty timeline for sequencing camera instructions.
     *
     * @return CameraTimeline A new timeline instance (use ->set(), ->fade(), ->wait(), etc. then ->play($player)).
     */
    public static function timeline() : CameraTimeline{
        return new CameraTimeline();
    }

}
