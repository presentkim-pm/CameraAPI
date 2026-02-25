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

namespace kim\present\cameraapi\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\NeverSavedWithChunkEntity;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Small helper entity used as an in-world marker for camera placement.
 *
 * This entity is sent to the client as a fake player (AddPlayerPacket) with a
 * configurable skin, so no custom resource pack is required. It is immobile,
 * does not save with chunks, and is damage-immune. Use {@see CameraMarker} to
 * control it; the plugin must call {@see self::setSkin()} before spawning any marker.
 */
final class CameraMarkerEntity extends Entity implements NeverSavedWithChunkEntity{

    private static Skin $skin;

    /**
     * Sets the skin used for all camera marker entities (e.g. a transparent or minimal skin).
     * Must be called before spawning any marker (typically in plugin onEnable).
     *
     * @param Skin $skin The skin data to send to the client for marker display.
     */
    public static function setSkin(Skin $skin) : void{
        self::$skin = $skin;
    }

    private UuidInterface $uuid;

    /** @var null|\Closure(CameraMarkerEntity, Player): void $onAttack Callback to player attack (left-click) */
    private ?\Closure $onAttack = null;

    /** @var null|\Closure(CameraMarkerEntity, Player): void $onClick Callback to player interact (right-click). */
    private ?\Closure $onClick = null;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->uuid = Uuid::uuid3(Uuid::NIL, (string) $this->getId());

        $this->setCanSaveWithChunk(false);
        $this->setNoClientPredictions(true);
        $this->setNameTagVisible(true);
        $this->setNameTagAlwaysVisible(true);
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(0.3, 0.3);
    }

    protected function getInitialDragMultiplier() : float{
        return 0.0;
    }

    protected function getInitialGravity() : float{
        return 0.0;
    }

    public static function getNetworkTypeId() : string{
        return EntityIds::PLAYER;
    }

    /**
     * Sends the fake player (AddPlayer) spawn packets so the marker appears with the configured skin.
     *
     * @param Player $player The player who will see the marker.
     */
    protected function sendSpawnPacket(Player $player) : void{
        $networkSession = $player->getNetworkSession();
        $typeConverter = $networkSession->getTypeConverter();

        $networkSession->sendDataPacket(PlayerListPacket::add([
            PlayerListEntry::createAdditionEntry(
                $this->uuid,
                $this->id,
                $this->getNameTag(),
                $typeConverter->getSkinAdapter()->toSkinData(self::$skin))
        ]));

        $networkSession->sendDataPacket(AddPlayerPacket::create(
            $this->uuid,
            $this->getNameTag(),
            $this->getId(),
            "",
            $this->location->asVector3(),
            $this->getMotion(),
            $this->location->pitch,
            $this->location->yaw,
            $this->location->yaw,
            ItemStackWrapper::legacy($typeConverter->coreItemStackToNet(VanillaItems::AIR())),
            GameMode::SURVIVAL,
            $this->getAllNetworkData(),
            new PropertySyncData([], []),
            UpdateAbilitiesPacket::create(new AbilitiesData(
                    CommandPermissions::NORMAL,
                    PlayerPermissions::VISITOR,
                    $this->getId(),
                    [])
            ),
            [],
            "",
            DeviceOS::UNKNOWN
        ));

        $this->sendData([$player],
            [EntityMetadataProperties::NAMETAG => new StringMetadataProperty($this->getNameTag())]);
        $networkSession->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($this->uuid)]));
    }

    public function attack(EntityDamageEvent $source) : void{
        // Prevent taking damage / being destroyed by hits.
        $source->cancel();
        if($this->onAttack !== null && $source instanceof EntityDamageByEntityEvent){
            $damager = $source->getDamager();
            ($this->onAttack)($this, $damager);
        }
    }

    /**
     * Invoked when a player interacts (right-clicks) this entity. Runs the registered onClick callback if set.
     *
     * @param Player  $player   The player who interacted.
     * @param Vector3 $clickPos The click position in world coordinates.
     *
     * @return bool True if the interaction was handled (callback was run).
     */
    public function onInteract(Player $player, Vector3 $clickPos) : bool{
        if($this->onClick === null){
            return false;
        }

        ($this->onClick)($this, $player);
        return true;
    }

    /**
     * Rotates the marker so that it visually looks at the given target (yaw and pitch).
     *
     * @param Vector3 $target World position to look at.
     *
     * @return self For chaining.
     */
    public function lookAt(Vector3 $target) : self{
        $xDist = $target->x - $this->location->x;
        $zDist = $target->z - $this->location->z;

        $horizontal = sqrt($xDist ** 2 + $zDist ** 2);
        $vertical = $target->y - ($this->location->y + $this->getEyeHeight());
        $pitch = -atan2($vertical, $horizontal) / M_PI * 180;

        $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if($yaw < 0){
            $yaw += 360.0;
        }

        $this->setRotation($yaw, $pitch);

        return $this;
    }

    /**
     * Registers a callback when a player attacks (left-clicks) this marker.
     *
     * @param null|\Closure(CameraMarkerEntity, Player): void $onAttack Callback (entity, player); null to clear.
     */
    public function setOnAttack(?\Closure $onAttack) : void{
        $this->onAttack = $onAttack;
    }

    /**
     * Registers a callback when a player interacts (right-clicks) this marker.
     *
     * @param null|\Closure(CameraMarkerEntity, Player): void $onClick Callback (entity, player); null to clear.
     */
    public function setOnClick(?\Closure $onClick) : void{
        $this->onClick = $onClick;
    }
}

