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

namespace kim\present\cameraapi\hud;

/**
 * Registry for reusable {@see HudPreset} definitions.
 *
 * Presets are stored under case-insensitive string keys and can be combined
 * with {@see HudPreset::send()} to switch HUD layouts in a single call.
 */
final class HudPresetRegistry{

    /** Built-in preset: all HUD elements visible. */
    public const PRESET_DEFAULT = 'DEFAULT';

    /** Built-in preset: all HUD elements hidden. */
    public const PRESET_CLEAR = 'CLEAR';

    /**
     * Registered presets (built-in + custom), keyed by lowercased name.
     *
     * @var array<string, HudPreset>
     */
    private static array $members;

    /**
     * Registers built-in presets. Called once by {@see self::checkInit()}.
     *
     * @internal
     */
    protected static function setup() : void{
        self::register(self::PRESET_DEFAULT, new HudPreset());
        self::register(self::PRESET_CLEAR, HudPreset::fromVisibleElements([]));
    }

    /**
     * Ensures the registry is initialized (built-in presets registered).
     *
     * @internal
     */
    protected static function checkInit() : void{
        if(!isset(self::$members)){
            self::$members = [];
            self::setup();
        }
    }

    /**
     * Registers a preset under the given name.
     *
     * Names are case-insensitive. Registering again under the same name
     * overwrites the previous preset (including built-in names).
     *
     * @param string    $name   Logical name for the preset.
     * @param HudPreset $preset The preset to register.
     */
    public static function register(string $name, HudPreset $preset) : void{
        self::checkInit();
        self::$members[strtolower($name)] = $preset;
    }

    /**
     * Retrieves a preset by name.
     *
     * @param string $name Case-insensitive preset name (e.g. {@see self::PRESET_DEFAULT}, {@see self::PRESET_CLEAR}).
     *
     * @return HudPreset|null The preset if found, null otherwise.
     */
    public static function get(string $name) : ?HudPreset{
        self::checkInit();

        return self::$members[\strtolower($name)] ?? null;
    }

    /**
     * Checks whether a preset with the given name is registered.
     *
     * @param string $name Case-insensitive preset name.
     *
     * @return bool
     */
    public static function isRegistered(string $name) : bool{
        return self::get($name) !== null;
    }

    /**
     * Returns all registered presets (built-in and custom), keyed by lowercased name.
     *
     * @return array<string, HudPreset>
     */
    public static function getAll() : array{
        self::checkInit();

        return self::$members;
    }

}

