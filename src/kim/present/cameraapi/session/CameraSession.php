<?php

declare(strict_types=1);

namespace kim\present\cameraapi\session;

use kim\present\cameraapi\builder\CameraFadeBuilder;
use kim\present\cameraapi\builder\CameraFogBuilder;
use kim\present\cameraapi\builder\CameraFovBuilder;
use kim\present\cameraapi\builder\CameraSetBuilder;
use kim\present\cameraapi\builder\CameraSplineBuilder;
use kim\present\cameraapi\builder\CameraTargetBuilder;
use kim\present\cameraapi\hud\HudPreset;
use kim\present\cameraapi\hud\HudPresetRegistry;
use kim\present\cameraapi\timeline\CameraTimeline;
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
    private ?string $waitingSignal = null;
    /** @var array<int, array{float, \Closure|string}> */
    private array $pausedTimelineQueue = [];
    private ?CameraTimeline $pausedTimeline = null;

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
     * Creates a builder for managing client-side Fog effects.
     * Used to push or remove fog settings.
     *
     * @return CameraFogBuilder
     */
    public function fog() : CameraFogBuilder{
        return new CameraFogBuilder($this);
    }

    /**
     * Applies a HUD preset to this session's player.
     *
     * Accepts either a {@see HudPreset} instance or a string name of a preset
     * registered in {@see HudPresetRegistry}. If a name is given and no preset
     * is found, throws {@see \InvalidArgumentException}.
     *
     * @param HudPreset|string $preset       A preset instance or a registry key (e.g.
     *                                       {@see HudPresetRegistry::PRESET_CLEAR}).
     *
     * @return self
     *
     * @throws \InvalidArgumentException When a string name is passed and no preset is registered under that name.
     */
    public function hud(HudPreset|string $preset) : self{
        if($preset instanceof HudPreset){
            $preset->send($this);
            return $this;
        }

        if(!HudPresetRegistry::isRegistered($preset)){
            throw new \InvalidArgumentException("Unknown HUD preset: " . $preset);
        }

        HudPresetRegistry::get($preset)->send($this);
        return $this;
    }

    /**
     * Creates a builder for 'Camera Spline' instruction.
     * Used to create smooth cinematic camera paths.
     *
     * @return CameraSplineBuilder
     * @deprecated Causes a fatal issue where the client drops the connection due to an internal error. Do not use
     *              until further notice.
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
     *
     * @return self
     */
    public function shake(
        float $intensity = 0.5,
        float $duration = 1.0,
        int $type = CameraShakePacket::TYPE_POSITIONAL
    ) : self{
        return $this->sendPacket(CameraShakePacket::create(
            $intensity,
            $duration,
            $type,
            CameraShakePacket::ACTION_ADD
        ));
    }

    /**
     * Stops any active camera shake.
     *
     * @param int $type The type of shake to stop.
     *
     * @return self
     */
    public function stopShake(int $type = CameraShakePacket::TYPE_POSITIONAL) : self{
        return $this->sendPacket(CameraShakePacket::create(
            0.0,
            0.0,
            $type,
            CameraShakePacket::ACTION_STOP
        ));
    }

    /**
     * Clears all camera instructions and resets to the default view.
     *
     * @return self
     */
    public function clear() : self{
        return $this->sendPacket(CameraInstructionPacket::create(
            set: null,
            clear: true,
            fade: null,
            target: null,
            removeTarget: null,
            fieldOfView: null,
            spline: null,
            attachToEntity: null,
            detachFromEntity: null
        ));
    }

    /**
     * Sends a packet to the player if they are online.
     *
     * @param ClientboundPacket $pk The packet to send (e.g. CameraInstructionPacket, CameraShakePacket).
     *
     * @return self
     */
    public function sendPacket(ClientboundPacket $pk) : self{
        $player = $this->getPlayer();
        if($player !== null && $player->isConnected()){
            $player->getNetworkSession()->sendDataPacket($pk);
        }
        return $this;
    }

    /**
     * Stops all active timeline tasks associated with this session.
     */
    public function stop() : self{
        foreach($this->activeTasks as $task){
            if(!$task->isCancelled()){
                $task->cancel();
            }
        }
        $this->activeTasks = [];

        $this->waitingSignal = null;
        $this->pausedTimelineQueue = [];
        $this->pausedTimeline = null;

        return $this;
    }

    /**
     * Registers a timeline task to be managed by this session (cancelled when {@see self::stop()} is called).
     *
     * @param TaskHandler $task The scheduled task handle returned by the scheduler.
     *
     * @return self
     */
    public function addTimelineTask(TaskHandler $task) : self{
        $this->activeTasks[] = $task;
        return $this;
    }

    /**
     * @param string                                    $signalName
     * @param array<int, array{float, \Closure|string}> $remainingQueue
     * @param CameraTimeline                            $timeline
     *
     * @internal Called by {@see CameraTimeline} when a signal wait is encountered.
     *
     */
    public function setWaitingSignal(string $signalName, array $remainingQueue, CameraTimeline $timeline) : void{
        $this->waitingSignal = $signalName;
        $this->pausedTimelineQueue = $remainingQueue;
        $this->pausedTimeline = $timeline;
    }

    /**
     * Emits a signal to this session. If the session is currently waiting for
     * the given signal, the paused timeline will resume from where it stopped.
     *
     * @param string $signalName
     *
     * @return self
     */
    public function emitSignal(string $signalName) : self{
        if($this->waitingSignal !== $signalName || $this->pausedTimeline === null){
            return $this;
        }

        $this->waitingSignal = null;

        $queueToResume = $this->pausedTimelineQueue;
        $this->pausedTimelineQueue = [];

        $timeline = $this->pausedTimeline;
        $this->pausedTimeline = null;

        $timeline->playFromQueue($this, $queueToResume);
        return $this;
    }
}
