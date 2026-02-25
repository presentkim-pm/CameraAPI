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

use kim\present\cameraapi\session\CameraSession;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\CameraInstructionPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraProgressOption;
use pocketmine\network\mcpe\protocol\types\camera\CameraRotationOption;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionEaseType;
use pocketmine\network\mcpe\protocol\types\camera\CameraSplineInstruction;

/**
 * Builder for constructing 'Camera Spline' instructions.
 *
 * This builder allows you to create cinematic camera paths:
 * - Define a series of points (Vector3)
 * - Set the total duration
 * - Apply rotation at specific times
 * - Control easing for movement
 *
 * Example:
 * ```php
 * $session->spline()
 *     ->time(5.0)
 *     ->addPoint($p1)->addPoint($p2)->addPoint($p3)
 *     ->ease(CameraSetInstructionEaseType::LINEAR)
 *     ->send();
 * ```
 *
 * @deprecated Causes the client to forcefully disconnect (crash) due to an error.
 *             This will be fixed once the root cause is identified.
 *
 * @todo       Investigate and resolve the client crash issue caused by spline data transmission
 */
final class CameraSplineBuilder{

    private float $totalTime = 20.0;
    private int $easeType = CameraSetInstructionEaseType::LINEAR;
    private array $curve = [];
    private array $progressKeyFrames = [];
    private array $rotationOptions = [];

    public function __construct(
        private readonly CameraSession $session
    ){}

    /**
     * Sets the total duration of the spline movement.
     *
     * @param float $seconds Total time in seconds.
     *
     * @return self
     */
    public function time(float $seconds) : self{
        $this->totalTime = $seconds;
        return $this;
    }

    /**
     * Sets the easing type for the movement along the spline.
     *
     * @param int $type The easing type (see {@link CameraSetInstructionEaseType} constants).
     *
     * @return self
     */
    public function ease(int $type) : self{
        $this->easeType = $type;
        return $this;
    }

    /**
     * Adds a point to the spline curve.
     * The camera will pass through these points in order.
     *
     * @param Vector3 $point
     *
     * @return self
     */
    public function addPoint(Vector3 $point) : self{
        $this->curve[] = $point;
        return $this;
    }

    /**
     * Adds a specific rotation at a specific time in the movement.
     *
     * @param Vector3 $rotation Pitch/Yaw/Roll vector.
     * @param float   $time     Time in seconds from start.
     *
     * @return self
     */
    public function addRotation(Vector3 $rotation, float $time) : self{
        $this->rotationOptions[] = new CameraRotationOption($rotation, $time);
        return $this;
    }

    /**
     * Adds a progress keyframe (advanced usage).
     * Used to trigger events or callbacks on client side (if supported).
     *
     * @param float $value    Progress value (0.0 - 1.0).
     * @param float $time     Time in seconds.
     * @param int   $easeType Easing for this segment.
     *
     * @return self
     */
    public function addProgress(float $value, float $time, int $easeType) : self{
        $this->progressKeyFrames[] = new CameraProgressOption($value, $time, $easeType);
        return $this;
    }

    /**
     * Builds the CameraSplineInstruction object.
     *
     * @return CameraSplineInstruction
     * @throws \InvalidArgumentException If the spline has fewer than 2 points or duration is <= 0.
     */
    public function build() : CameraSplineInstruction{
        if($this->totalTime <= 0){
            throw new \InvalidArgumentException("Camera spline duration must be greater than 0.");
        }
        if(count($this->curve) < 2){
            throw new \InvalidArgumentException("Camera spline must have at least 2 points to prevent client crashes.");
        }

        return new CameraSplineInstruction(
            $this->totalTime,
            $this->easeType,
            $this->curve,
            $this->progressKeyFrames,
            $this->rotationOptions
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
            set: null,
            clear: null,
            fade: null,
            target: null,
            removeTarget: null,
            fieldOfView: null,
            spline: $instruction,
            attachToEntity: null,
            detachFromEntity: null
        );
        $this->session->sendPacket($pk);

        return $this->session;
    }
}
