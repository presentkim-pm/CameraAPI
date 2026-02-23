<?php

declare(strict_types=1);

namespace kim\present\cameraapi\session;

use kim\present\cameraapi\builder\CameraFadeBuilder;
use kim\present\cameraapi\builder\CameraFovBuilder;
use kim\present\cameraapi\builder\CameraSetBuilder;
use kim\present\cameraapi\builder\CameraSplineBuilder;
use kim\present\cameraapi\builder\CameraTargetBuilder;
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
     * Creates a builder for 'Camera Set' instruction.
     * Used to position and rotate the camera.
     *
     * @return CameraSetBuilder
     */
    public function set() : CameraSetBuilder{
        return new CameraSetBuilder($this);
    }

    /**
     * Creates a builder for 'Camera Fade' instruction.
     * Used to control screen fading (color, time).
     *
     * @return CameraFadeBuilder
     */
    public function fade() : CameraFadeBuilder{
        return new CameraFadeBuilder($this);
    }

    /**
     * Creates a builder for 'Camera Target' instruction.
     * Used to make the camera track an entity or position.
     *
     * @return CameraTargetBuilder
     */
    public function target() : CameraTargetBuilder{
        return new CameraTargetBuilder($this);
    }

    /**
     * Creates a builder for 'Camera FOV' instruction.
     * Used to change the Field of View.
     *
     * @return CameraFovBuilder
     */
    public function fov() : CameraFovBuilder{
        return new CameraFovBuilder($this);
    }

    /**
     * Creates a builder for 'Camera Spline' instruction.
     * Used to create smooth cinematic camera paths.
     *
     * @return CameraSplineBuilder
     */
    public function spline() : CameraSplineBuilder{
        return new CameraSplineBuilder($this);
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
