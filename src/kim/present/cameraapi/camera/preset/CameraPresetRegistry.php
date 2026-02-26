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

use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\CameraPresetsPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraPreset;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * Registry for managing and synchronizing all camera presets.
 *
 * This manager handles the sequential ID allocation starting from 0.
 * These IDs are mapped to the order of registration and are strictly required
 * for the Minecraft client to correctly identify presets via network packets.
 */
final class CameraPresetRegistry{

    /**
     * Init from {@see self::checkInit()}
     *
     * @var CameraPresetData[]
     */
    private static array $members;

    private static int $newPresetId = 0;

    public const PRESET_FIRST_PERSON = "minecraft:first_person";
    public const PRESET_FIXED_BOOM = "minecraft:fixed_boom";
    public const PRESET_FOLLOW_ORBIT = "minecraft:follow_orbit";
    public const PRESET_FREE = "minecraft:free";
    public const PRESET_THIRD_PERSON = "minecraft:third_person";
    public const PRESET_THIRD_PERSON_FRONT = "minecraft:third_person_front";

    protected static function setup() : void{
        self::registerVanillaPresets();
        self::sendToAll();
    }

    /**
     * Register vanilla camera presets.
     *
     * These presets are registered in a specific order to mirror the vanilla Minecraft BE client.
     *
     * The values (offsets, radius, etc.) are derived from analyzing vanilla 'CameraPresetsPacket'
     * to ensure strict protocol compatibility and behavior consistency.
     */
    private static function registerVanillaPresets() : void{
        self::register(CameraPresetBuilder::createDirect(self::PRESET_FIRST_PERSON));
        self::register(CameraPresetBuilder::createDirect(
            name: self::PRESET_FIXED_BOOM,
            viewOffset: new Vector2(0, 0),
            entityOffset: new Vector3(0, 0, 0)
        ));
        self::register(CameraPresetBuilder::createDirect(
            name: self::PRESET_FOLLOW_ORBIT,
            viewOffset: new Vector2(0, 0),
            entityOffset: new Vector3(0, 0, 0),
            radius: 10
        ));
        self::register(CameraPresetBuilder::createDirect(
            name: self::PRESET_FREE,
            x: 0,
            y: 0,
            z: 0,
            pitch: 0,
            yaw: 0,
            audioListenerType: CameraPreset::AUDIO_LISTENER_TYPE_CAMERA
        ));
        self::register(CameraPresetBuilder::createDirect(self::PRESET_THIRD_PERSON));
        self::register(CameraPresetBuilder::createDirect(self::PRESET_THIRD_PERSON_FRONT));
    }

    /**
     * @throws \InvalidArgumentException
     * @internal Lazy-inits the enum if necessary.
     *
     */
    protected static function checkInit() : void{
        if(!isset(self::$members)){
            self::$members = [];
            self::setup();
        }
    }

    /**
     * Registers a camera preset and generates a CameraPresetData object.
     *
     * This method assigns a unique sequential ID to the preset based on the current
     * registration count, ensuring compatibility with the client-side preset index.
     *
     * @param CameraPreset $preset The preset object, typically built via {@see CameraPresetBuilder}.
     *
     * @return CameraPresetData The immutable data record for the registered preset.
     * @throws \InvalidArgumentException If a preset with the same name is already registered.
     */
    public static function register(CameraPreset $preset) : CameraPresetData{
        self::checkInit();

        $lowerName = strtolower($preset->getName());
        if(isset(self::$members[$lowerName])){
            throw new \InvalidArgumentException("\"$lowerName\" is already reserved");
        }

        return self::$members[$lowerName] = new CameraPresetData($lowerName, $preset, self::$newPresetId++);
    }

    /**
     * Retrieves a registered camera preset by its name.
     *
     * @param string $name The case-insensitive unique identifier (e.g., "minecraft:free").
     *
     * @return CameraPresetData|null The preset data object if found, or null otherwise.
     */
    public static function get(string $name) : ?CameraPresetData{
        self::checkInit();

        $lowerName = strtolower($name);
        if(!isset(self::$members[$lowerName])){
            throw new \InvalidArgumentException("No such registry member: " . $lowerName);
        }

        return self::$members[$lowerName];
    }

    /**
     * Retrieves the network-compatible ID associated with a preset name.
     *
     * @param string $name The unique identifier of the preset.
     *
     * @return int|null The sequential ID as an integer, or null if the preset is not registered.
     */
    public static function getIdByName(string $name) : ?int{
        return self::get($name)?->getId();
    }

    /**
     * Determines whether a camera preset is currently registered in the registry.
     *
     * @param string $name The case-insensitive unique identifier of the preset.
     *
     * @return bool True if registered, false otherwise.
     */
    public static function isRegistered(string $name) : bool{
        self::checkInit();
        return isset(self::$members[strtoupper($name)]);
    }

    /**
     * Returns an array of all currently registered camera preset data.
     *
     * @return CameraPresetData[] A list containing all registered presets.
     */
    public static function getAll() : array{
        self::checkInit();
        return array_values(self::$members);
    }

    /**
     * Aggregates all registered presets into a CameraPresetsPacket.
     *
     * This packet must be sent to the client to initialize the available camera
     * types before any specific camera commands or transitions can be executed.
     *
     * @return CameraPresetsPacket The packet containing all preset definitions.
     */
    public static function createCameraPresetsPacket() : CameraPresetsPacket{
        return CameraPresetsPacket::create(array_map(
            fn(CameraPresetData $data) : CameraPreset => $data->getPreset(),
            self::getAll()
        ));
    }

    /**
     * Broadcasts the full list of camera presets to all players currently online.
     */
    public static function sendToAll() : void{
        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            self::sendTo($player);
        }
    }

    /**
     * Synchronizes the camera preset list with a specific player.
     *
     * This ensures the client-side registry matches the server-side definitions,
     * which is a prerequisite for using custom or vanilla camera presets.
     *
     * @param Player $player The recipient of the presets packet.
     */
    public static function sendTo(Player $player) : void{
        $player->getNetworkSession()->sendDataPacket(self::createCameraPresetsPacket());
    }

}
