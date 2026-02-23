<?php

declare(strict_types=1);

namespace kim\present\cameraapi\session;

use pocketmine\network\mcpe\protocol\CameraInstructionPacket;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;

/**
 * Manages the camera state and operations for a single player.
 *
 * This class provides a fluent interface to access various camera builders
 * and execute camera instructions.
 */
final class CameraSession{

    /** @var TaskHandler[] */
    private array $activeTasks = [];
    private \WeakReference $playerRef;

    /**
     * @param Player $player The player associated with this session.
     */
    public function __construct(Player $player){
        $this->playerRef = \WeakReference::create($player);
    }

    /**
     * Retrieves the player object if they are still online.
     *
     * @return Player|null The player instance or null if offline.
     */
    public function getPlayer() : ?Player{
        return $this->playerRef->get();
    }

    /**
     * Sends a camera shake packet to the player.
     *
     * @param float $intensity Intensity of the shake (0.0 - 1.0 recommended).
     * @param float $duration  Duration in seconds.
     * @param int   $type      The type of shake (positional or rotational).
     */
    public function shake(float $intensity = 0.5, float $duration = 1.0, int $type = CameraShakePacket::TYPE_POSITIONAL
    ) : void{
        $this->sendPacket(CameraShakePacket::create($intensity, $duration, $type, CameraShakePacket::ACTION_ADD));
    }

    /**
     * Stops any active camera shake.
     *
     * @param int $type The type of shake to stop.
     */
    public function stopShake(int $type = CameraShakePacket::TYPE_POSITIONAL) : void{
        $this->sendPacket(CameraShakePacket::create(0.0, 0.0, $type, CameraShakePacket::ACTION_STOP));
    }

    /**
     * Clears all camera instructions and resets to the default view.
     */
    public function clear() : void{
        $pk = CameraInstructionPacket::create(
            set: null,
            clear: true,
            fade: null,
            target: null,
            removeTarget: null,
            fieldOfView: null,
            spline: null,
            attachToEntity: null,
            detachFromEntity: null
        );
        $this->sendPacket($pk);
    }

    /**
     * Sends a packet to the player if they are online.
     *
     * @param ClientboundPacket $pk
     */
    public function sendPacket(ClientboundPacket $pk) : void{
        $player = $this->getPlayer();
        if($player !== null && $player->isConnected()){
            $player->getNetworkSession()->sendDataPacket($pk);
        }
    }

    /**
     * Stops all active timeline tasks associated with this session.
     */
    public function stop() : void{
        foreach($this->activeTasks as $task){
            if(!$task->isCancelled()){
                $task->cancel();
            }
        }
        $this->activeTasks = [];
    }

    /**
     * Registers a timeline task to be managed by this session.
     *
     * @param TaskHandler $task
     */
    public function addTimelineTask(TaskHandler $task) : void{
        $this->activeTasks[] = $task;
    }
}
