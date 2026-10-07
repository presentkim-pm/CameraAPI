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

namespace kim\present\cameraapi\session;

use pocketmine\player\Player;

/**
 * Manages the lifecycle of CameraSessions.
 *
 * This class maintains a registry of active camera sessions for online players.
 * It uses a WeakMap to automatically clean up sessions when Player objects are destroyed,
 * preventing memory leaks.
 *
 * Internal use mostly, but can be accessed via `Camera::of()`.
 */
final class CameraSessionManager{

    /** @var \WeakMap<Player, CameraSession>|null */
    private static ?\WeakMap $sessions = null;

    /**
     * Returns the session storage, creating it on first use.
     *
     * Lazy creation lets other plugins call `Camera::of()` before this plugin is enabled, and keeps
     * {@see self::close()} safe when the plugin failed to enable.
     *
     * @return \WeakMap<Player, CameraSession>
     */
    private static function sessions() : \WeakMap{
        return self::$sessions ??= new \WeakMap();
    }

    /**
     * Initializes the session storage.
     * Called when the plugin enables. Sessions that already exist are kept.
     */
    public static function init() : void{
        self::sessions();
    }

    /**
     * Cleans up all sessions.
     * Called when the plugin disables.
     */
    public static function close() : void{
        foreach(self::sessions() as $session){
            $session->stop();
        }
    }

    /**
     * Creates a new session for a player.
     *
     * @param Player $player
     *
     * @return CameraSession
     */
    public static function createSession(Player $player) : CameraSession{
        $session = new CameraSession($player);
        self::sessions()[$player] = $session;
        return $session;
    }

    /**
     * Retrieves an existing session for a player.
     *
     * @param Player $player
     *
     * @return CameraSession|null Returns null if no session exists.
     */
    public static function getSession(Player $player) : ?CameraSession{
        return self::sessions()[$player] ?? null;
    }

    /**
     * Removes and stops a player's session.
     *
     * @param Player $player
     */
    public static function removeSession(Player $player) : void{
        $sessions = self::sessions();
        if(isset($sessions[$player])){
            $sessions[$player]->stop();
            unset($sessions[$player]);
        }
    }
}
