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

namespace kim\present\cameraapi\preset;

use pocketmine\network\mcpe\protocol\types\camera\CameraPreset;

/**
 * A read-only data container that links a CameraPreset with its registration metadata.
 *
 * This class encapsulates the preset's unique name, the internal PMMP preset object,
 * and the critical sequential ID (index) required for Minecraft network synchronization.
 */
final readonly class CameraPresetData{

    /**
     * @param string       $name   The unique string identifier (e.g., 'namespace:name').
     * @param CameraPreset $preset The immutable PMMP camera preset object.
     * @param int          $id     The zero-based sequential index for packet communication.
     */
    public function __construct(
        private string $name,
        private CameraPreset $preset,
        private int $id
    ){}

    /**
     * Returns the unique string identifier of this preset.
     *
     * @return string
     */
    public function getName() : string{
        return $this->name;
    }

    /**
     * Returns the network ID used for synchronization.
     *
     * @return int
     */
    public function getId() : int{
        return $this->id;
    }

    /**
     * Returns the underlying PMMP CameraPreset object.
     *
     * @return CameraPreset
     */
    public function getPreset() : CameraPreset{
        return $this->preset;
    }

}
