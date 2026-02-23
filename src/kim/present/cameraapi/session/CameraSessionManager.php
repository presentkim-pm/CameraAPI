<?php

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

    /** @var \WeakMap<int, CameraSession> */
    private static \WeakMap $sessions;

    /**
     * Initializes the session storage.
     * Called when the plugin enables.
     */
    public static function init() : void{
        self::$sessions = new \WeakMap();
    }

    /**
     * Cleans up all sessions.
     * Called when the plugin disables.
     */
    public static function close() : void{
        foreach(self::$sessions as $session){
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
        self::$sessions[$player] = $session;
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
        return self::$sessions[$player] ?? null;
    }

    /**
     * Removes and stops a player's session.
     *
     * @param Player $player
     */
    public static function removeSession(Player $player) : void{
        if(isset(self::$sessions[$player])){
            self::$sessions[$player]->stop();
            unset(self::$sessions[$player]);
        }
    }
}
