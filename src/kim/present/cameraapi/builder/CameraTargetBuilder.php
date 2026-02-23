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

namespace kim\present\cameraapi\builder;

use kim\present\cameraapi\session\CameraSession;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\CameraInstructionPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraTargetInstruction;

/**
 * Builder for constructing 'Camera Target' instructions.
 *
 * This builder allows you to make the camera track:
 * - A specific Entity (by object or ID)
 * - A coordinate offset from the target
 *
 * Example:
 * ```php
 * $session->target()
 *     ->entity($player)
 *     ->offset(new Vector3(0, 1.5, 0)) // Look at head
 *     ->send();
 * ```
 */
final class CameraTargetBuilder{

    private ?Vector3 $offset = null;
    private ?int $entityId = null;

    public function __construct(
        private readonly CameraSession $session
    ){}

    /**
     * Sets the offset from the target's center to look at.
     *
     * @param Vector3 $offset
     *
     * @return self
     */
    public function offset(Vector3 $offset) : self{
        $this->offset = $offset;
        return $this;
    }

    /**
     * Sets the target entity to track.
     *
     * @param Entity $entity
     *
     * @return self
     */
    public function entity(Entity $entity) : self{
        $this->entityId = $entity->getId();
        return $this;
    }

    /**
     * Sets the target entity ID manually.
     *
     * @param int $id The runtime ID of the entity.
     *
     * @return self
     */
    public function entityId(int $id) : self{
        $this->entityId = $id;
        return $this;
    }

    /**
     * Builds the CameraTargetInstruction object.
     *
     * @return CameraTargetInstruction
     */
    public function build() : CameraTargetInstruction{
        return new CameraTargetInstruction($this->offset, $this->entityId);
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
            target: $instruction,
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
