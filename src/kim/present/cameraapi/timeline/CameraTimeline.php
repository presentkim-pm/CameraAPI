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

namespace kim\present\cameraapi\timeline;

use kim\present\cameraapi\builder\CameraFadeBuilder;
use kim\present\cameraapi\builder\CameraFovBuilder;
use kim\present\cameraapi\builder\CameraSetBuilder;
use kim\present\cameraapi\builder\CameraSplineBuilder;
use kim\present\cameraapi\builder\CameraTargetBuilder;
use kim\present\cameraapi\Camera;
use kim\present\cameraapi\Main;
use kim\present\cameraapi\session\CameraSession;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

/**
 * A reusable timeline for sequencing camera instructions.
 *
 * This class allows you to create complex camera scenes (cutscenes) by queuing
 * various instructions (set, fade, spline, wait, etc.) and playing them back
 * for a specific player.
 *
 * Example:
 * ```php
 * $timeline = new CameraTimeline();
 * $timeline
 *     ->fade(fn($b) => $b->in(0.5))
 *     ->wait(0.5)
 *     ->set(fn($b) => $b->preset("minecraft:free")->position($pos))
 *     ->wait(5.0)
 *     ->clear();
 *
 * $timeline->play($player);
 * ```
 */
final class CameraTimeline{

    /** @var array<int, array{float, \Closure}> */
    private array $queue = [];
    private float $currentTime = 0.0;
    private bool $loop = false;

    public function __construct(){}

    /**
     * Adds a delay before the next instruction executes.
     *
     * @param float $seconds Delay in seconds.
     *
     * @return self
     */
    public function wait(float $seconds) : self{
        $this->currentTime += $seconds;
        return $this;
    }

    /**
     * Adds a custom action to the timeline at the current time offset.
     *
     * @param \Closure(CameraSession): void $action Callback receiving the player's camera session
     *
     * @return self For chaining.
     */
    public function add(\Closure $action) : self{
        $this->queue[] = [$this->currentTime, $action];
        return $this;
    }

    /**
     * Enables or disables looping this timeline. When enabled, the full
     * sequence will automatically restart after it finishes, until the
     * underlying CameraSession is stopped.
     *
     * @param bool $loop
     *
     * @return self
     */
    public function setLoop(bool $loop = true) : self{
        $this->loop = $loop;
        return $this;
    }

    /**
     * Adds a 'Camera Set' instruction to the timeline.
     *
     * @param \Closure(CameraSetBuilder): void $setup Callback to configure the builder.
     *
     * @return self
     */
    public function set(\Closure $setup) : self{
        return $this->add(function(CameraSession $session) use ($setup){
            $builder = new CameraSetBuilder($session);
            $setup($builder);
            $builder->send();
        });
    }

    /**
     * Adds a 'Camera Fade' instruction to the timeline.
     *
     * @param \Closure(CameraFadeBuilder): void $setup Callback to configure the builder.
     *
     * @return self
     */
    public function fade(\Closure $setup) : self{
        return $this->add(function(CameraSession $session) use ($setup){
            $builder = new CameraFadeBuilder($session);
            $setup($builder);
            $builder->send();
        });
    }

    /**
     * Adds a 'Camera Target' instruction to the timeline.
     *
     * @param \Closure(CameraTargetBuilder): void $setup Callback to configure the builder.
     *
     * @return self
     */
    public function target(\Closure $setup) : self{
        return $this->add(function(CameraSession $session) use ($setup){
            $builder = new CameraTargetBuilder($session);
            $setup($builder);
            $builder->send();
        });
    }

    /**
     * Adds a 'Camera FOV' instruction to the timeline.
     *
     * @param \Closure(CameraFovBuilder): void $setup Callback to configure the builder.
     *
     * @return self
     */
    public function fov(\Closure $setup) : self{
        return $this->add(function(CameraSession $session) use ($setup){
            $builder = new CameraFovBuilder($session);
            $setup($builder);
            $builder->send();
        });
    }

    /**
     * Adds a 'Camera Spline' instruction to the timeline.
     *
     * @param \Closure(CameraSplineBuilder): void $setup Callback to configure the builder.
     *
     * @return self
     */
    public function spline(\Closure $setup) : self{
        return $this->add(function(CameraSession $session) use ($setup){
            $builder = new CameraSplineBuilder($session);
            $setup($builder);
            $builder->send();
        });
    }

    /**
     * Adds a camera shake effect to the timeline.
     *
     * @param float $intensity Shake intensity.
     * @param float $duration  Duration in seconds.
     * @param int   $type      Shake type.
     *
     * @return self
     */
    public function shake(float $intensity = 0.5, float $duration = 1.0, int $type = CameraShakePacket::TYPE_POSITIONAL
    ) : self{
        return $this->add(function(CameraSession $session) use ($intensity, $duration, $type){
            $session->shake($intensity, $duration, $type);
        });
    }

    /**
     * Adds a command to stop camera shake.
     *
     * @param int $type Shake type to stop.
     *
     * @return self
     */
    public function stopShake(int $type = CameraShakePacket::TYPE_POSITIONAL) : self{
        return $this->add(function(CameraSession $session) use ($type){
            $session->stopShake($type);
        });
    }

    /**
     * Adds a 'Clear' instruction to reset the camera.
     *
     * @return self
     */
    public function clear() : self{
        return $this->add(function(CameraSession $session){
            $session->clear();
        });
    }

    /**
     * Plays the timeline for the specified player.
     * This schedules all queued instructions using the server scheduler.
     *
     * @param Player|CameraSession $player
     *
     * @return void
     */
    public function play(Player|CameraSession $player) : void{
        $session = $player instanceof CameraSession ? $player : Camera::of($player);
        $session->stop(); // Cancel currently running timeline tasks

        $scheduler = Main::getInstance()->getScheduler();

        foreach($this->queue as [$delay, $action]){
            $tickDelay = (int) ($delay * 20);
            if($tickDelay <= 0){
                $action($session);
            }else{
                $task = $scheduler->scheduleDelayedTask(new ClosureTask(function() use ($action, $session){
                    if($session->getPlayer() !== null && $session->getPlayer()->isConnected()){
                        $action($session);
                    }
                }), $tickDelay);
                $session->addTimelineTask($task);
            }
        }

        // Automatically loop the entire sequence if requested.
        if($this->loop && $this->currentTime > 0){
            $totalTicks = (int) ($this->currentTime * 20);
            $loopTask = $scheduler->scheduleDelayedTask(new ClosureTask(function() use ($session) : void{
                if($session->getPlayer() !== null && $session->getPlayer()->isConnected()){
                    $this->play($session);
                }
            }), $totalTicks);
            $session->addTimelineTask($loopTask);
        }
    }
}
