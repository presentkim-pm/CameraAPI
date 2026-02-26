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

/**
 * Vanilla fog IDs for use with {@see \kim\present\cameraapi\builder\CameraFogBuilder}.
 *
 * These strings are the same as those used by the Minecraft client for biome/effect fog.
 * Availability may depend on client version and resource packs.
 *
 * @see https://wiki.bedrock.dev/documentation/fog-ids
 */
final class VanillaFogIds{

    public const FOG_BAMBOO_JUNGLE = "minecraft:fog_bamboo_jungle";
    public const FOG_BAMBOO_JUNGLE_HILLS = "minecraft:fog_bamboo_jungle_hills";
    public const FOG_BASALT_DELTAS = "minecraft:fog_basalt_deltas";
    public const FOG_BEACH = "minecraft:fog_beach";
    public const FOG_BIRCH_FOREST = "minecraft:fog_birch_forest";
    public const FOG_BIRCH_FOREST_HILLS = "minecraft:fog_birch_forest_hills";
    public const FOG_CHERRY_GROVE = "minecraft:fog_cherry_grove";
    public const FOG_COLD_BEACH = "minecraft:fog_cold_beach";
    public const FOG_COLD_OCEAN = "minecraft:fog_cold_ocean";
    public const FOG_COLD_TAIGA = "minecraft:fog_cold_taiga";
    public const FOG_COLD_TAIGA_HILLS = "minecraft:fog_cold_taiga_hills";
    public const FOG_COLD_TAIGA_MUTATED = "minecraft:fog_cold_taiga_mutated";
    public const FOG_CRIMSON_FOREST = "minecraft:fog_crimson_forest";
    public const FOG_DEEP_COLD_OCEAN = "minecraft:fog_deep_cold_ocean";
    public const FOG_DEEP_FROZEN_OCEAN = "minecraft:fog_deep_frozen_ocean";
    public const FOG_DEEP_LUKEWARM_OCEAN = "minecraft:fog_deep_lukewarm_ocean";
    public const FOG_DEEP_OCEAN = "minecraft:fog_deep_ocean";
    public const FOG_DEEP_WARM_OCEAN = "minecraft:fog_deep_warm_ocean";
    public const FOG_DEFAULT = "minecraft:fog_default";
    public const FOG_DESERT = "minecraft:fog_desert";
    public const FOG_DESERT_HILLS = "minecraft:fog_desert_hills";
    public const FOG_EXTREME_HILLS = "minecraft:fog_extreme_hills";
    public const FOG_EXTREME_HILLS_EDGE = "minecraft:fog_extreme_hills_edge";
    public const FOG_EXTREME_HILLS_MUTATED = "minecraft:fog_extreme_hills_mutated";
    public const FOG_EXTREME_HILLS_PLUS_TREES = "minecraft:fog_extreme_hills_plus_trees";
    public const FOG_EXTREME_HILLS_PLUS_TREES_MUTATED = "minecraft:fog_extreme_hills_plus_trees_mutated";
    public const FOG_FLOWER_FOREST = "minecraft:fog_flower_forest";
    public const FOG_FOREST = "minecraft:fog_forest";
    public const FOG_FOREST_HILLS = "minecraft:fog_forest_hills";
    public const FOG_FROZEN_OCEAN = "minecraft:fog_frozen_ocean";
    public const FOG_FROZEN_RIVER = "minecraft:fog_frozen_river";
    public const FOG_HELL = "minecraft:fog_hell";
    public const FOG_ICE_MOUNTAINS = "minecraft:fog_ice_mountains";
    public const FOG_ICE_PLAINS = "minecraft:fog_ice_plains";
    public const FOG_ICE_PLAINS_SPIKES = "minecraft:fog_ice_plains_spikes";
    public const FOG_JUNGLE = "minecraft:fog_jungle";
    public const FOG_JUNGLE_EDGE = "minecraft:fog_jungle_edge";
    public const FOG_JUNGLE_HILLS = "minecraft:fog_jungle_hills";
    public const FOG_JUNGLE_MUTATED = "minecraft:fog_jungle_mutated";
    public const FOG_LUKEWARM_OCEAN = "minecraft:fog_lukewarm_ocean";
    public const FOG_MANGROVE_SWAMP = "minecraft:fog_mangrove_swamp";
    public const FOG_MEGA_SPRUCE_TAIGA = "minecraft:fog_mega_spruce_taiga";
    public const FOG_MEGA_SPRUCE_TAIGA_MUTATED = "minecraft:fog_mega_spruce_taiga_mutated";
    public const FOG_MEGA_TAIGA = "minecraft:fog_mega_taiga";
    public const FOG_MEGA_TAIGA_HILLS = "minecraft:fog_mega_taiga_hills";
    public const FOG_MEGA_TAIGA_MUTATED = "minecraft:fog_mega_taiga_mutated";
    public const FOG_MESA = "minecraft:fog_mesa";
    public const FOG_MESA_BRYCE = "minecraft:fog_mesa_bryce";
    public const FOG_MESA_MUTATED = "minecraft:fog_mesa_mutated";
    public const FOG_MESA_PLATEAU = "minecraft:fog_mesa_plateau";
    public const FOG_MESA_PLATEAU_STONE = "minecraft:fog_mesa_plateau_stone";
    public const FOG_MUSHROOM_ISLAND = "minecraft:fog_mushroom_island";
    public const FOG_MUSHROOM_ISLAND_SHORE = "minecraft:fog_mushroom_island_shore";
    public const FOG_OCEAN = "minecraft:fog_ocean";
    public const FOG_PLAINS = "minecraft:fog_plains";
    public const FOG_RIVER = "minecraft:fog_river";
    public const FOG_ROOFED_FOREST = "minecraft:fog_roofed_forest";
    public const FOG_SAVANNA = "minecraft:fog_savanna";
    public const FOG_SAVANNA_MUTATED = "minecraft:fog_savanna_mutated";
    public const FOG_SAVANNA_PLATEAU = "minecraft:fog_savanna_plateau";
    public const FOG_SOULSAND_VALLEY = "minecraft:fog_soulsand_valley";
    public const FOG_STONE_BEACH = "minecraft:fog_stone_beach";
    public const FOG_SUNFLOWER_PLAINS = "minecraft:fog_sunflower_plains";
    public const FOG_SWAMPLAND = "minecraft:fog_swampland";
    public const FOG_SWAMPLAND_MUTATED = "minecraft:fog_swampland_mutated";
    public const FOG_TAIGA = "minecraft:fog_taiga";
    public const FOG_TAIGA_HILLS = "minecraft:fog_taiga_hills";
    public const FOG_TAIGA_MUTATED = "minecraft:fog_taiga_mutated";
    public const FOG_THE_END = "minecraft:fog_the_end";
    public const FOG_WARM_OCEAN = "minecraft:fog_warm_ocean";
    public const FOG_WARPED_FOREST = "minecraft:fog_warped_forest";

    private function __construct(){
        // NOOP
    }
}
