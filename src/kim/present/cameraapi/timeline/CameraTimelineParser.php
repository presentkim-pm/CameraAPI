<?php

declare(strict_types=1);

namespace kim\present\cameraapi\timeline;

use kim\present\cameraapi\builder\CameraFadeBuilder;
use kim\present\cameraapi\builder\CameraFovBuilder;
use kim\present\cameraapi\builder\CameraSetBuilder;
use kim\present\cameraapi\session\CameraSession;
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
     *              'type'   => 'wait' | 'waitUntil' | 'shake' | 'stopShake' | 'clear' | 'set' | 'fade' | 'fov',
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
}

