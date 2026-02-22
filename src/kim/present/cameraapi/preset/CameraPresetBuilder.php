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

use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\camera\CameraPreset;
use pocketmine\network\mcpe\protocol\types\camera\CameraPresetAimAssist;
use pocketmine\network\mcpe\protocol\types\ControlScheme;

/**
 * A fluent builder for creating Minecraft CameraPreset instances.
 *
 * Provides a user-friendly way to configure complex camera properties without
 * dealing with the massive constructor of the final CameraPreset class.
 * All values are optional unless required by the specific camera type.
 */
class CameraPresetBuilder{

    private string $parent = "";
    private ?float $x = null, $y = null, $z = null;
    private ?float $pitch = null, $yaw = null;
    private ?float $rotationSpeed = null;
    private ?bool $snapToTarget = null;
    private ?Vector2 $horizontalRotationLimit = null, $verticalRotationLimit = null;
    private ?bool $continueTargeting = null;
    private ?float $blockListeningRadius = null;
    private ?Vector2 $viewOffset = null;
    private ?Vector3 $entityOffset = null;
    private ?float $radius = null;
    private ?float $yawLimitMin = null, $yawLimitMax = null;
    private int $audioListenerType = CameraPreset::AUDIO_LISTENER_TYPE_PLAYER;
    private ?bool $playerEffects = null;
    private ?CameraPresetAimAssist $aimAssist = null;
    private ?ControlScheme $controlScheme = null;

    public function __construct(private readonly string $name){}

    public static function create(string $name) : self{
        return new self($name);
    }

    public function setParent(string $parent) : self{
        $this->parent = $parent;
        return $this;
    }

    /**
     * Sets the position using a Vector3 object.
     * (A shorthand for setting x, y, and z components individually)
     */
    public function setPos(Vector3 $pos) : self{
        $this->x = $pos->x;
        $this->y = $pos->y;
        $this->z = $pos->z;

        return $this;
    }

    public function setX(?float $x) : self{
        $this->x = $x;
        return $this;
    }

    public function setY(?float $y) : self{
        $this->y = $y;
        return $this;
    }

    public function setZ(?float $z) : self{
        $this->z = $z;
        return $this;
    }

    public function setPitch(?float $pitch) : self{
        $this->pitch = $pitch;
        return $this;
    }

    public function setYaw(?float $yaw) : self{
        $this->yaw = $yaw;
        return $this;
    }

    public function setRotationSpeed(?float $rotationSpeed) : self{
        $this->rotationSpeed = $rotationSpeed;
        return $this;
    }

    public function setSnapToTarget(?bool $snapToTarget) : self{
        $this->snapToTarget = $snapToTarget;
        return $this;
    }

    public function setHorizontalRotationLimit(?Vector2 $horizontalRotationLimit) : self{
        $this->horizontalRotationLimit = $horizontalRotationLimit;
        return $this;
    }

    public function setVerticalRotationLimit(?Vector2 $verticalRotationLimit) : self{
        $this->verticalRotationLimit = $verticalRotationLimit;
        return $this;
    }

    public function setContinueTargeting(?bool $continueTargeting) : self{
        $this->continueTargeting = $continueTargeting;
        return $this;
    }

    public function setBlockListeningRadius(?float $blockListeningRadius) : self{
        $this->blockListeningRadius = $blockListeningRadius;
        return $this;
    }

    public function setViewOffset(?Vector2 $viewOffset) : self{
        $this->viewOffset = $viewOffset;
        return $this;
    }

    public function setEntityOffset(?Vector3 $entityOffset) : self{
        $this->entityOffset = $entityOffset;
        return $this;
    }

    public function setRadius(?float $radius) : self{
        $this->radius = $radius;
        return $this;
    }

    public function setYawLimitMin(?float $yawLimitMin) : self{
        $this->yawLimitMin = $yawLimitMin;
        return $this;
    }

    public function setYawLimitMax(?float $yawLimitMax) : self{
        $this->yawLimitMax = $yawLimitMax;
        return $this;
    }

    public function setAudioListenerType(int $audioListenerType) : self{
        $this->audioListenerType = $audioListenerType;
        return $this;
    }

    public function setPlayerEffects(?bool $playerEffects) : self{
        $this->playerEffects = $playerEffects;
        return $this;
    }

    public function setAimAssist(?CameraPresetAimAssist $aimAssist) : self{
        $this->aimAssist = $aimAssist;
        return $this;
    }

    public function setControlScheme(?ControlScheme $controlScheme) : self{
        $this->controlScheme = $controlScheme;
        return $this;
    }

    /**
     * Builds the final CameraPreset object.
     */
    public function build() : CameraPreset{
        return new CameraPreset(
            $this->name, $this->parent, $this->x, $this->y, $this->z,
            $this->pitch, $this->yaw, $this->rotationSpeed, $this->snapToTarget,
            $this->horizontalRotationLimit, $this->verticalRotationLimit,
            $this->continueTargeting, $this->blockListeningRadius,
            $this->viewOffset, $this->entityOffset, $this->radius,
            $this->yawLimitMin, $this->yawLimitMax, $this->audioListenerType,
            $this->playerEffects, $this->aimAssist, $this->controlScheme
        );
    }

    /**
     * A static helper to create a CameraPreset using PHP's named arguments.
     *
     * Since the PMMP CameraPreset constructor lacks default values for its nullable parameters,
     * this method provides them as null by default, allowing you to skip unnecessary arguments.
     */
    public static function createDirect(
        string $name,
        string $parent = "",
        ?float $x = null,
        ?float $y = null,
        ?float $z = null,
        ?float $pitch = null,
        ?float $yaw = null,
        ?float $rotationSpeed = null,
        ?bool $snapToTarget = null,
        ?Vector2 $horizontalRotationLimit = null,
        ?Vector2 $verticalRotationLimit = null,
        ?bool $continueTargeting = null,
        ?float $blockListeningRadius = null,
        ?Vector2 $viewOffset = null,
        ?Vector3 $entityOffset = null,
        ?float $radius = null,
        ?float $yawLimitMin = null,
        ?float $yawLimitMax = null,
        ?int $audioListenerType = null,
        ?bool $playerEffects = null,
        ?CameraPresetAimAssist $aimAssist = null,
        ?ControlScheme $controlScheme = null,
    ) : CameraPreset{
        return new CameraPreset(
            $name,
            $parent,
            $x,
            $y,
            $z,
            $pitch,
            $yaw,
            $rotationSpeed,
            $snapToTarget,
            $horizontalRotationLimit,
            $verticalRotationLimit,
            $continueTargeting,
            $blockListeningRadius,
            $viewOffset,
            $entityOffset,
            $radius,
            $yawLimitMin,
            $yawLimitMax,
            $audioListenerType,
            $playerEffects,
            $aimAssist,
            $controlScheme
        );
    }
}
