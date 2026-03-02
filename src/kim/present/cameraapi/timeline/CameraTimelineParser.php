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

use kim\present\cameraapi\camera\builder\CameraFadeBuilder;
use kim\present\cameraapi\camera\builder\CameraFogBuilder;
use kim\present\cameraapi\camera\builder\CameraFovBuilder;
use kim\present\cameraapi\camera\builder\CameraSetBuilder;
use kim\present\cameraapi\camera\builder\CameraTargetBuilder;
use kim\present\cameraapi\session\CameraSession;
use kim\present\cameraapi\utils\ControlSchemePackets;
use pocketmine\math\Vector3;

/**
 * Utility for constructing {@see CameraTimeline} instances from array / JSON data.
 *
 * This allows you to describe cutscenes in data (e.g. JSON files) instead of PHP
 * code, which is especially useful when non-programmers need to tweak timings
 * or positions without touching code.
 */
final class CameraTimelineParser{

    /**
     * Builds a {@see CameraTimeline} from a JSON string.
     *
     * @param string $json
     *
     * @return CameraTimeline
     */
    public static function fromJson(string $json) : CameraTimeline{
        $data = json_decode($json, true);
        if(!is_array($data)){
            throw new \InvalidArgumentException("Invalid JSON format for CameraTimeline.");
        }

        return self::fromArray($data);
    }

    /**
     * Builds a {@see CameraTimeline} from an associative array.
     *
     * Expected schema (simplified):
     *
     *  [
     *      'loop' => bool,
     *      'steps' => [
     *          [
     *              'type'   => 'wait' | 'waitUntil' | 'shake' | 'stopShake' | 'clear' | 'set' | 'fade' | 'fov'
     *                          | 'fog' | 'controlScheme' | 'target' | 'attachToEntity' | 'detachFromEntity',
     *              // ... additional fields depending on type ...
     *          ],
     *          // ...
     *      ]
     *  ]
     *
     * @param array<string, mixed> $data
     *
     * @return CameraTimeline
     */
    public static function fromArray(array $data) : CameraTimeline{
        $timeline = new CameraTimeline();

        if(isset($data['loop']) && is_bool($data['loop'])){
            $timeline->setLoop($data['loop']);
        }

        $steps = $data['steps'] ?? [];
        if(!is_array($steps)){
            return $timeline;
        }

        foreach($steps as $step){
            if(!is_array($step)){
                continue;
            }

            $type = $step['type'] ?? '';

            switch($type){
                case 'wait':
                    $timeline->wait((float) ($step['seconds'] ?? 0.0));
                    break;

                case 'waitUntil':
                    $signal = (string) ($step['signal'] ?? '');
                    if($signal !== ''){
                        $timeline->waitUntil($signal);
                    }
                    break;

                case 'shake':
                    $intensity = (float) ($step['intensity'] ?? 0.5);
                    $duration = (float) ($step['duration'] ?? 1.0);
                    $timeline->shake($intensity, $duration);
                    break;

                case 'stopShake':
                    $timeline->stopShake();
                    break;

                case 'clear':
                    $timeline->clear();
                    break;

                case 'set':
                    self::addSetStep($timeline, $step);
                    break;

                case 'fade':
                    self::addFadeStep($timeline, $step);
                    break;

                case 'fov':
                    self::addFovStep($timeline, $step);
                    break;

                case 'fog':
                    self::addFogStep($timeline, $step);
                    break;

                case 'controlScheme':
                    self::addControlSchemeStep($timeline, $step);
                    break;

                case 'target':
                    self::addTargetStep($timeline, $step);
                    break;

                case 'attachToEntity':
                    self::addAttachToEntityStep($timeline, $step);
                    break;

                case 'detachFromEntity':
                    $timeline->detachFromEntity();
                    break;

                default:
                    // Unknown step type: ignore for forwards-compatibility.
                    break;
            }
        }

        return $timeline;
    }

    /**
     * Adds a "set" step to the timeline.
     *
     * @param CameraTimeline       $timeline
     * @param array<string, mixed> $step
     */
    private static function addSetStep(CameraTimeline $timeline, array $step) : void{
        $timeline->add(
        /**
         * @param CameraSession $session
         */
            function(CameraSession $session) use ($step) : void{
                $builder = new CameraSetBuilder($session);

                if(isset($step['preset'])){
                    $builder->preset((string) $step['preset']);
                }

                if(isset($step['position']) && is_array($step['position']) && count($step['position']) >= 3){
                    $builder->position(
                        new Vector3(
                            (float) $step['position'][0],
                            (float) $step['position'][1],
                            (float) $step['position'][2]
                        )
                    );
                }

                if(isset($step['facing']) && is_array($step['facing']) && count($step['facing']) >= 3){
                    $builder->facing(
                        new Vector3(
                            (float) $step['facing'][0],
                            (float) $step['facing'][1],
                            (float) $step['facing'][2]
                        )
                    );
                }

                if(isset($step['rotation']) && is_array($step['rotation']) && count($step['rotation']) >= 2){
                    $builder->rotation(
                        (float) $step['rotation'][0],
                        (float) $step['rotation'][1]
                    );
                }

                if(isset($step['ease']) && is_array($step['ease'])){
                    $builder->ease(
                        (int) ($step['ease']['type'] ?? 0),
                        (float) ($step['ease']['duration'] ?? 0.0)
                    );
                }

                $builder->send();
            }
        );
    }

    /**
     * Adds a "fade" step to the timeline.
     *
     * @param CameraTimeline       $timeline
     * @param array<string, mixed> $step
     */
    private static function addFadeStep(CameraTimeline $timeline, array $step) : void{
        $timeline->add(
        /**
         * @param CameraSession $session
         */
            function(CameraSession $session) use ($step) : void{
                $builder = new CameraFadeBuilder($session);

                if(isset($step['in'])){
                    $builder->in((float) $step['in']);
                }
                if(isset($step['stay'])){
                    $builder->stay((float) $step['stay']);
                }
                if(isset($step['out'])){
                    $builder->out((float) $step['out']);
                }

                $builder->send();
            }
        );
    }

    /**
     * Adds a "fov" step to the timeline.
     *
     * @param CameraTimeline       $timeline
     * @param array<string, mixed> $step
     */
    private static function addFovStep(CameraTimeline $timeline, array $step) : void{
        $timeline->add(
        /**
         * @param CameraSession $session
         */
            function(CameraSession $session) use ($step) : void{
                $builder = new CameraFovBuilder($session);

                if(isset($step['set'])){
                    $builder->set((float) $step['set']);
                }
                if(isset($step['ease']) && is_array($step['ease'])){
                    $builder->ease(
                        (int) ($step['ease']['type'] ?? 0),
                        (float) ($step['ease']['duration'] ?? 0.0)
                    );
                }

                $builder->send();
            }
        );
    }

    /**
     * Adds a "fog" step to the timeline.
     *
     * Schema:
     *  - push: array of { fogId: string, userProvidedId: string }
     *  - remove: array of userProvidedId strings
     *
     * @param CameraTimeline       $timeline
     * @param array<string, mixed> $step
     */
    private static function addFogStep(CameraTimeline $timeline, array $step) : void{
        $push = isset($step['push']) && is_array($step['push']) ? $step['push'] : [];
        $remove = isset($step['remove']) && is_array($step['remove']) ? array_map('strval', $step['remove']) : [];
        $timeline->add(
            function(CameraSession $session) use ($push, $remove) : void{
                $builder = new CameraFogBuilder($session);

                foreach($remove as $userProvidedId){
                    $builder->remove($userProvidedId);
                }

                foreach($push as $item){
                    if(!is_array($item)){
                        continue;
                    }

                    $fogId = isset($item['fogId']) ? (string) $item['fogId'] : null;
                    $userProvidedId = isset($item['userProvidedId']) ? (string) $item['userProvidedId'] : null;
                    if($fogId === null || $userProvidedId === null){
                        continue;
                    }

                    $builder->push($fogId, $userProvidedId);
                }

                $builder->send();
            }
        );
    }

    /**
     * Adds a "controlScheme" step to the timeline.
     *
     * @param CameraTimeline       $timeline
     * @param array<string, mixed> $step
     */
    private static function addControlSchemeStep(CameraTimeline $timeline, array $step) : void{
        $schemeName = (string) ($step['scheme'] ?? '');
        if($schemeName === ''){
            return;
        }
        $allowed = [
            'LOCKED_PLAYER_RELATIVE_STRAFE', 'CAMERA_RELATIVE', 'CAMERA_RELATIVE_STRAFE',
            'PLAYER_RELATIVE', 'PLAYER_RELATIVE_STRAFE',
        ];
        if(!in_array($schemeName, $allowed, true)){
            return;
        }
        $packet = call_user_func([ControlSchemePackets::class, $schemeName]);
        $timeline->controlScheme($packet);
    }

    /**
     * Adds a "target" step to the timeline.
     *
     * @param CameraTimeline       $timeline
     * @param array<string, mixed> $step
     */
    private static function addTargetStep(CameraTimeline $timeline, array $step) : void{
        $timeline->add(
            function(CameraSession $session) use ($step) : void{
                $builder = new CameraTargetBuilder($session);
                if(isset($step['entityId'])){
                    $builder->entityId((int) $step['entityId']);
                }else{
                    $builder->entityId(null);
                }
                if(isset($step['offset']) && is_array($step['offset']) && count($step['offset']) >= 3){
                    $builder->offset(new Vector3(
                        (float) $step['offset'][0],
                        (float) $step['offset'][1],
                        (float) $step['offset'][2]
                    ));
                }
                $builder->send();
            }
        );
    }

    /**
     * Adds an "attachToEntity" step to the timeline (by runtime ID).
     *
     * @param CameraTimeline       $timeline
     * @param array<string, mixed> $step
     */
    private static function addAttachToEntityStep(CameraTimeline $timeline, array $step) : void{
        $runtimeId = isset($step['entityId']) ? (int) $step['entityId'] : (isset($step['runtimeId']) ? (int) $step['runtimeId'] : null);
        if($runtimeId === null){
            return;
        }
        $timeline->attachToEntity($runtimeId);
    }
}

