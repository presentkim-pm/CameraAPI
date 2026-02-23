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
use pocketmine\network\mcpe\protocol\CameraInstructionPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraFovInstruction;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionEaseType as EaseType;

/**
 * Builder for constructing 'Camera FOV' instructions.
 *
 * This builder allows you to:
 * - Set the Field of View value
 * - Apply easing animations for FOV changes
 *
 * Example:
 * ```php
 * $session->fov()
 *     ->set(90.0)
 *     ->ease(EaseType::IN_CUBIC, 1.0)
 *     ->send();
 * ```
 */
final class CameraFovBuilder{

    private float $fov = 70.0;
    private float $easeTime = 0.0;
    private int $easeType = EaseType::LINEAR;

    public function __construct(
        private readonly CameraSession $session
    ){}

    /**
     * Sets the target Field of View.
     *
     * @param float $fov FOV in degrees.
     *
     * @return self
     */
    public function set(float $fov) : self{
        $this->fov = $fov;
        return $this;
    }

    /**
     * Sets the easing animation for the FOV change.
     *
     * @param int   $type     The easing type (see {@link EaseType} constants).
     * @param float $duration Duration in seconds.
     *
     * @return self
     */
    public function ease(int $type, float $duration) : self{
        $this->easeType = $type;
        $this->easeTime = $duration;
        return $this;
    }

    /**
     * Builds the CameraFovInstruction object.
     *
     * @return CameraFovInstruction
     */
    public function build() : CameraFovInstruction{
        return new CameraFovInstruction($this->fov, $this->easeTime, $this->easeType, false);
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
            fieldOfView: $instruction,
            spline: null,
            attachToEntity: null,
            detachFromEntity: null
        );
        $this->session->sendPacket($pk);

        return $this->session;
    }
}
