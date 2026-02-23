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

namespace kim\present\cameraapi\builder;

use kim\present\cameraapi\preset\CameraPresetRegistry;
use kim\present\cameraapi\session\CameraSession;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\CameraInstructionPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstruction;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionEase;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionRotation;

/**
 * Builder for constructing 'Camera Set' instructions.
 *
 * This builder allows you to configure:
 * - Camera Preset (e.g., 'minecraft:free')
 * - Position and Rotation
 * - Easing (transition animations)
 * - Facing specific coordinates
 * - Offsets (View/Entity)
 *
 * Example:
 * ```php
 * $session->set()
 *     ->preset("minecraft:free")
 *     ->position($pos)
 *     ->rotation(90, 0)
 *     ->ease(EaseType::LINEAR, 2.0)
 *     ->send();
 * ```
 */
final class CameraSetBuilder{

    private ?string $preset = null;
    private ?CameraSetInstructionEase $ease = null;
    private ?Vector3 $cameraPosition = null;
    private ?CameraSetInstructionRotation $rotation = null;
    private ?Vector3 $facingPosition = null;
    private ?Vector2 $viewOffset = null;
    private ?Vector3 $entityOffset = null;
    private bool $default = false;

    public function __construct(
        private readonly CameraSession $session
    ){}

    /**
     * Sets the camera preset (e.g., "minecraft:free", "minecraft:first_person").
     *
     * @param string $preset The name of the preset.
     *
     * @return self
     */
    public function preset(string $preset) : self{
        $this->preset = $preset;
        return $this;
    }

    /**
     * Sets the easing animation for the camera movement.
     *
     * @param int   $type     The easing type (see {@link EaseType} constants).
     * @param float $duration The duration of the transition in seconds.
     *
     * @return self
     */
    public function ease(int $type, float $duration) : self{
        $this->ease = new CameraSetInstructionEase($type, $duration);
        return $this;
    }

    /**
     * Sets the absolute position of the camera.
     *
     * @param Vector3 $position
     *
     * @return self
     */
    public function position(Vector3 $position) : self{
        $this->cameraPosition = $position;
        return $this;
    }

    /**
     * Sets the rotation of the camera.
     *
     * @param float $pitch Pitch angle (vertical).
     * @param float $yaw   Yaw angle (horizontal).
     *
     * @return self
     */
    public function rotation(float $pitch, float $yaw) : self{
        $this->rotation = new CameraSetInstructionRotation($pitch, $yaw);
        return $this;
    }

    /**
     * Makes the camera face a specific position.
     *
     * @param Vector3 $position The target position to look at.
     *
     * @return self
     */
    public function facing(Vector3 $position) : self{
        $this->facingPosition = $position;
        return $this;
    }

    /**
     * Sets the view offset relative to the screen center.
     *
     * @param Vector2 $offset
     *
     * @return self
     */
    public function viewOffset(Vector2 $offset) : self{
        $this->viewOffset = $offset;
        return $this;
    }

    /**
     * Sets the offset relative to the entity's position.
     *
     * @param Vector3 $offset
     *
     * @return self
     */
    public function entityOffset(Vector3 $offset) : self{
        $this->entityOffset = $offset;
        return $this;
    }

    /**
     * Marks this instruction as the default camera state.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setDefault(bool $value = true) : self{
        $this->default = $value;
        return $this;
    }

    /**
     * Builds the CameraSetInstruction object.
     *
     * @return CameraSetInstruction
     */
    public function build() : CameraSetInstruction{
        $presetId = -1;
        if($this->preset !== null){
            $presetId = CameraPresetRegistry::getIdByName($this->preset) ?? -1;
        }

        return new CameraSetInstruction(
            $presetId,
            $this->ease,
            $this->cameraPosition,
            $this->rotation,
            $this->facingPosition,
            $this->viewOffset,
            $this->entityOffset,
            $this->default,
            false
        );
    }

    /**
     * Sends the constructed packet to the player.
     *
     * @return CameraSession Returns the session for method chaining.
     */
    public function send() : CameraSession{
        $instruction = $this->build();
        $pk = CameraInstructionPacket::create(
            set: $instruction,
            clear: null,
            fade: null,
            target: null,
            removeTarget: null,
            fieldOfView: null,
            spline: null,
            attachToEntity: null,
            detachFromEntity: null
        );
        $this->session->sendPacket($pk);

        return $this->session;
    }
}
