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

use kim\present\cameraapi\marker\CameraMarker;
use kim\present\cameraapi\marker\CameraMarkerEntity;
use kim\present\cameraapi\session\CameraSession;
use kim\present\cameraapi\session\CameraSessionManager;
use kim\present\cameraapi\timeline\CameraTimeline;
use kim\present\cameraapi\timeline\CameraTimelineParser;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

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

    /**
     * Creates a timeline from a JSON string using {@see CameraTimelineParser}.
     *
     * This is a convenience entry point for loading cutscenes described in
     * external JSON files.
     *
     * Example:
     *  $json = file_get_contents($this->getDataFolder() . "cutscenes/boss_intro.json");
     *  $timeline = Camera::loadTimeline($json);
     *
     * @param string $json JSON string describing the timeline.
     *
     * @return CameraTimeline
     */
    public static function loadTimeline(string $json) : CameraTimeline{
        return CameraTimelineParser::fromJson($json);
    }

    /**
     * Spawns a small helper marker entity that can be used to define camera
     * positions and orientations in the world.
     *
     * The marker:
     * - Uses a fake-player representation (no custom resource pack required).
     * - Is static and damage-immune.
     * - Supports optional interact button and on-attack / on-click callbacks via the returned {@link CameraMarker}.
     *
     * @param Location    $location World coordinates (and yaw/pitch) where the marker should appear.
     * @param string|null $label    Optional name tag shown above the marker.
     *
     * @return CameraMarker Wrapper to move, rotate, apply to a session, or remove the marker.
     */
    public static function spawnMarker(Location $location, ?string $label = null) : CameraMarker{
        $world = $location->getWorld();
        self::ensureChunkLoaded($world, $location);

        $entity = new CameraMarkerEntity($location);

        if($label !== null){
            $entity->setNameTag($label);
        }

        $entity->spawnToAll();

        return new CameraMarker($entity);
    }

    /**
     * Ensures the chunk at the given position is loaded so an entity can be spawned.
     *
     * @param World   $world The world to load the chunk in.
     * @param Vector3 $pos   Position (x, z used for chunk coordinates).
     */
    private static function ensureChunkLoaded(World $world, Vector3 $pos) : void{
        $cx = $pos->getFloorX() >> 4;
        $cz = $pos->getFloorZ() >> 4;
        if(!$world->isChunkLoaded($cx, $cz)){
            $world->loadChunk($cx, $cz);
        }
    }

}
