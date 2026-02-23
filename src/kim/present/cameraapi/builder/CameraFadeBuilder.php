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
use kim\present\cameraapi\utils\ColorUtils;
use pocketmine\block\utils\DyeColor;
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\CameraInstructionPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraFadeInstruction;
use pocketmine\network\mcpe\protocol\types\camera\CameraFadeInstructionColor;
use pocketmine\network\mcpe\protocol\types\camera\CameraFadeInstructionTime;

/**
 * Builder for constructing 'Camera Fade' instructions.
 *
 * This builder allows you to configure screen fading effects:
 * - Fade In time (darkening)
 * - Stay time (fully dark)
 * - Fade Out time (clearing)
 * - Color of the fade (default: Black)
 *
 * Example:
 * ```php
 * $session->fade()
 *     ->in(0.5)->stay(1.0)->out(0.5)
 *     ->color(Color::RED)
 *     ->send();
 * ```
 */
final class CameraFadeBuilder{

    private float $fadeIn = 0.5;
    private float $stay = 0.5;
    private float $fadeOut = 0.5;
    private ?Color $color = null;

    public function __construct(
        private readonly CameraSession $session
    ){}

    /**
     * Sets the fade-in duration (time to turn fully opaque).
     *
     * @param float $seconds Duration in seconds.
     *
     * @return self
     */
    public function in(float $seconds) : self{
        $this->fadeIn = $seconds;
        return $this;
    }

    /**
     * Sets the duration to stay fully opaque.
     *
     * @param float $seconds Duration in seconds.
     *
     * @return self
     */
    public function stay(float $seconds) : self{
        $this->stay = $seconds;
        return $this;
    }

    /**
     * Sets the fade-out duration (time to clear the screen).
     *
     * @param float $seconds Duration in seconds.
     *
     * @return self
     */
    public function out(float $seconds) : self{
        $this->fadeOut = $seconds;
        return $this;
    }

    /**
     * Sets the color of the fade curtain.
     *
     * @param Color $color The color to use (default is Black).
     *
     * @return self
     */
    public function color(Color $color) : self{
        $this->color = $color;
        return $this;
    }

    /**
     * Builds the CameraFadeInstruction object.
     *
     * @return CameraFadeInstruction
     */
    public function build() : CameraFadeInstruction{
        $rgb = ColorUtils::normalizeRgb($this->color ?? DyeColor::BLACK());
        return new CameraFadeInstruction(
            new CameraFadeInstructionTime($this->fadeIn, $this->stay, $this->fadeOut),
            new CameraFadeInstructionColor($rgb[0], $rgb[1], $rgb[2])
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
            fade: $instruction,
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
