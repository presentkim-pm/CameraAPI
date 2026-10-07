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

namespace kim\present\cameraapi\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistActionType;
use pocketmine\player\Player;

/**
 * Called when a client reports that its aim assist was switched on or off ({@see \pocketmine\network\mcpe\protocol\ClientCameraAimAssistPacket}).
 *
 * The session state ({@see \kim\present\cameraapi\session\CameraSession::isClientAimAssistAllowed()}) is updated
 * before this event is called.
 */
final class CameraAimAssistToggleEvent extends PlayerEvent{

    public function __construct(
        Player $player,
        private readonly string $presetId,
        private readonly CameraAimAssistActionType $actionType,
        private readonly bool $allowAimAssist
    ){
        $this->player = $player;
    }

    /** Identifier of the aim assist preset the client reported about. */
    public function getPresetId() : string{
        return $this->presetId;
    }

    /** Whether the client set or cleared the preset. */
    public function getActionType() : CameraAimAssistActionType{
        return $this->actionType;
    }

    /** Whether the client allows aim assist (the player's setting). */
    public function isAimAssistAllowed() : bool{
        return $this->allowAimAssist;
    }
}
