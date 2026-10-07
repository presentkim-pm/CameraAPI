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

namespace kim\present\cameraapi\aimassist;

use kim\present\cameraapi\session\CameraSession;
use pocketmine\network\mcpe\protocol\CameraAimAssistActorPriorityPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistActorPriorityData;

/**
 * Builder for overriding the aim assist priority of individual actors ({@see CameraAimAssistActorPriorityPacket}).
 *
 * The protocol addresses a priority entry by the positions of the preset, the category and the actor in the
 * lists sent by {@see AimAssistPresetRegistry}. This builder takes identifiers and resolves those positions, so
 * the entries have to be registered in the registry before they are added here.
 *
 * Example:
 * ```php
 * $session->aimAssistActorPriority()
 *     ->add(DefaultAimAssistPresetIds::MINECRAFT_DEFAULT, DefaultAimAssistPresetIds::CATEGORY_DEFAULT, "minecraft:cow", 99)
 *     ->send();
 * ```
 *
 * Note: the packet has not been verified against a real client yet.
 */
final class AimAssistActorPriorityBuilder{

    /** @var list<CameraAimAssistActorPriorityData> */
    private array $entries = [];

    public function __construct(
        private readonly CameraSession $session
    ){}

    /**
     * Adds a priority override for an actor of a category.
     *
     * @param string $presetId   Identifier of a registered aim assist preset.
     * @param string $categoryId Identifier of a registered aim assist category.
     * @param string $actorId    Entity identifier listed in the category's entity priorities.
     * @param int    $priority   New priority of the actor.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the preset, the category or the actor is not registered.
     */
    public function add(string $presetId, string $categoryId, string $actorId, int $priority) : self{
        $presetIndex = AimAssistPresetRegistry::getPresetIndex($presetId)
            ?? throw new \InvalidArgumentException("Unknown aim assist preset: " . $presetId);
        $categoryIndex = AimAssistPresetRegistry::getCategoryIndex($categoryId)
            ?? throw new \InvalidArgumentException("Unknown aim assist category: " . $categoryId);

        $actorIndex = null;
        $category = AimAssistPresetRegistry::getCategory($categoryId);
        foreach($category?->getPriorities()->getEntities() ?? [] as $index => $entity){
            if($entity->getIdentifier() === $actorId){
                $actorIndex = $index;
                break;
            }
        }
        if($actorIndex === null){
            throw new \InvalidArgumentException("Actor $actorId is not listed in aim assist category $categoryId");
        }

        $this->entries[] = new CameraAimAssistActorPriorityData($presetIndex, $categoryIndex, $actorIndex, $priority);
        return $this;
    }

    /**
     * Sends the collected priority overrides to the player.
     *
     * @return CameraSession Returns the session for method chaining.
     */
    public function send() : CameraSession{
        return $this->session->sendPacket(CameraAimAssistActorPriorityPacket::create($this->entries));
    }
}
