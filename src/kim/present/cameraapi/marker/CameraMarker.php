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

namespace kim\present\cameraapi\marker;

use kim\present\cameraapi\camera\preset\CameraPresetRegistry;
use kim\present\cameraapi\entity\CameraMarkerEntity;
use kim\present\cameraapi\session\CameraSession;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionEaseType;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

/**
 * Lightweight wrapper around the internal marker entity.
 *
 * This class exposes a small, convenient API for map creators to control
 * camera markers without dealing with low-level entity logic. Use {@see Camera::spawnMarker()}
 * to create a marker, then chain {@see self::setOnAttack()}, {@see self::setOnClick()},
 * and {@see self::applyToSession()} as needed.
 */
final readonly class CameraMarker{

    /**
     * @param CameraMarkerEntity $entity The backing entity (fake player) used for this marker.
     */
    public function __construct(
        private CameraMarkerEntity $entity
    ){}

    /**
     * Returns the underlying marker entity for advanced use.
     *
     * @return CameraMarkerEntity
     */
    public function getEntity() : CameraMarkerEntity{
        return $this->entity;
    }

    /**
     * Teleports the marker to the specified position in its current world.
     *
     * @param Vector3 $pos Target coordinates (same world as the marker).
     *
     * @return self For chaining.
     */
    public function setPosition(Vector3 $pos) : self{
        $this->entity->teleport($pos);
        return $this;
    }

    /**
     * Sets the name tag text shown above the marker.
     *
     * @param string $name Display name (e.g. "Camera #1").
     *
     * @return self For chaining.
     */
    public function setNameTag(string $name) : self{
        $this->entity->setNameTag($name);
        return $this;
    }

    /**
     * Sets the interact button label shown when the player looks at the marker (e.g. "Test").
     *
     * @param string $buttonText Text shown on the interact hint.
     *
     * @return self For chaining.
     */
    public function setInteractButton(string $buttonText) : self{
        $this->entity->getNetworkProperties()->setString(EntityMetadataProperties::INTERACTIVE_TAG, $buttonText);
        return $this;
    }

    /**
     * Rotates the marker so that it visually looks at a target point.
     *
     * @param Vector3 $target World position to look at.
     *
     * @return self For chaining.
     */
    public function lookAt(Vector3 $target) : self{
        $this->entity->lookAt($target);
        return $this;
    }

    /**
     * Sets a callback invoked when a player attacks (left-clicks) the marker.
     *
     * @param null|\Closure(CameraMarker, Player): void $onAttack Callback (marker, player); null to clear.
     *
     * @return self For chaining.
     */
    public function setOnAttack(?\Closure $onAttack) : self{
        $this->entity->setOnAttack($onAttack === null ? null : fn($_, Player $player) => $onAttack($this, $player));
        return $this;
    }

    /**
     * Sets a callback invoked when a player interacts (right-clicks) the marker.
     *
     * @param null|\Closure(CameraMarker, Player): void $onClick Callback (marker, player); null to clear.
     *
     * @return self For chaining.
     */
    public function setOnClick(?\Closure $onClick) : self{
        $this->entity->setOnClick($onClick === null ? null : fn($_, Player $player) => $onClick($this, $player));
        return $this;
    }

    /**
     * Applies this marker's pose to a CameraSession.
     *
     * The camera will be positioned at the marker's location, using its
     * current yaw / pitch and the vanilla FREE preset. Optionally applies
     * an ease (transition) so the camera moves smoothly to this pose.
     *
     * @param CameraSession $session      The session to apply this marker's position and rotation to.
     * @param int|null      $easeType     Easing type (e.g. {@see CameraSetInstructionEaseType::LINEAR}); null to skip
     *                                    ease.
     * @param float|null    $easeDuration Duration of the transition in seconds; used only when $easeType is not null.
     *
     * @return CameraSession The same session for chaining.
     */
    public function applyToSession(
        CameraSession $session,
        ?int $easeType = null,
        ?float $easeDuration = null
    ) : CameraSession{
        $location = $this->entity->getLocation();

        $builder = $session->set()
                           ->preset(CameraPresetRegistry::PRESET_FREE)
                           ->position($location->asVector3())
                           ->rotation($location->pitch, $location->yaw);

        if($easeType !== null && $easeDuration !== null && $easeDuration > 0.0){
            $builder->ease($easeType, $easeDuration);
        }

        return $builder->send();
    }

    /**
     * Despawns the marker from the world and frees references.
     */
    public function remove() : void{
        $this->entity->flagForDespawn();
    }
}

