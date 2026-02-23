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

namespace kim\present\cameraapi;

use kim\present\cameraapi\preset\CameraPresetRegistry;
use kim\present\cameraapi\session\CameraSessionManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class Main extends PluginBase implements Listener{
    use SingletonTrait;

    protected function onLoad() : void{
        self::setInstance($this);
    }

    protected function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        CameraSessionManager::init();
    }

    protected function onDisable() : void{
        CameraSessionManager::close();
    }

    /** @priority MONITOR */
    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        CameraSessionManager::createSession($player);
        CameraPresetRegistry::sendTo($player);
    }

    /** @priority MONITOR */
    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        CameraSessionManager::removeSession($event->getPlayer());
    }

    /** @priority LOWEST */
    public function onDataPacketSend(DataPacketSendEvent $event) : void{
        foreach($event->getPackets() as $packet){
            if($packet instanceof StartGamePacket){
                $this->enableExperimentalCreatorCameras($packet->levelSettings->experiments);
            }elseif($packet instanceof ResourcePackStackPacket){
                $this->enableExperimentalCreatorCameras($packet->experiments);
            }
        }
    }

    private function enableExperimentalCreatorCameras(Experiments $experiments) : void{
        (function() use ($experiments) : void{
            $experiments->experiments["experimental_creator_cameras"] = true;
        })->bindTo($this, Experiments::class)();
    }

}
