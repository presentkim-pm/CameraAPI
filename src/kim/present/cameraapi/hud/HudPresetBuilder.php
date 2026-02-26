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

namespace kim\present\cameraapi\hud;

/**
 * Fluent builder for {@see HudPreset}.
 *
 * This is mainly useful if you prefer a chainable API over PHP named
 * parameters on {@see HudPreset}'s constructor.
 */
final class HudPresetBuilder{

    private bool $paperDoll = true;
    private bool $armor = true;
    private bool $tooltips = true;
    private bool $touchControls = true;
    private bool $crosshair = true;
    private bool $hotbar = true;
    private bool $health = true;
    private bool $xp = true;
    private bool $food = true;
    private bool $airBubbles = true;
    private bool $horseHealth = true;
    private bool $statusEffects = true;
    private bool $itemText = true;

    /**
     * Creates a new builder with all HUD elements enabled by default.
     *
     * @return self
     */
    public static function create() : self{
        return new self();
    }

    /**
     * Sets paper doll (player model) visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function paperDoll(bool $value = true) : self{
        $this->paperDoll = $value;
        return $this;
    }

    /**
     * Sets armor bar visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function armor(bool $value = true) : self{
        $this->armor = $value;
        return $this;
    }

    /**
     * Sets tooltip visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function tooltips(bool $value = true) : self{
        $this->tooltips = $value;
        return $this;
    }

    /**
     * Sets touch control hints visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function touchControls(bool $value = true) : self{
        $this->touchControls = $value;
        return $this;
    }

    /**
     * Sets crosshair visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function crosshair(bool $value = true) : self{
        $this->crosshair = $value;
        return $this;
    }

    /**
     * Sets hotbar visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function hotbar(bool $value = true) : self{
        $this->hotbar = $value;
        return $this;
    }

    /**
     * Sets health bar visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function health(bool $value = true) : self{
        $this->health = $value;
        return $this;
    }

    /**
     * Sets experience bar visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function xp(bool $value = true) : self{
        $this->xp = $value;
        return $this;
    }

    /**
     * Sets food bar visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function food(bool $value = true) : self{
        $this->food = $value;
        return $this;
    }

    /**
     * Sets air bubbles (underwater) visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function airBubbles(bool $value = true) : self{
        $this->airBubbles = $value;
        return $this;
    }

    /**
     * Sets horse health bar visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function horseHealth(bool $value = true) : self{
        $this->horseHealth = $value;
        return $this;
    }

    /**
     * Sets status effect icons visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function statusEffects(bool $value = true) : self{
        $this->statusEffects = $value;
        return $this;
    }

    /**
     * Sets item name text visibility.
     *
     * @param bool $value
     *
     * @return self
     */
    public function itemText(bool $value = true) : self{
        $this->itemText = $value;
        return $this;
    }

    /**
     * Disables every HUD element (all flags set to false).
     *
     * @return self
     */
    public function hideAll() : self{
        $this->paperDoll = false;
        $this->armor = false;
        $this->tooltips = false;
        $this->touchControls = false;
        $this->crosshair = false;
        $this->hotbar = false;
        $this->health = false;
        $this->xp = false;
        $this->food = false;
        $this->airBubbles = false;
        $this->horseHealth = false;
        $this->statusEffects = false;
        $this->itemText = false;
        return $this;
    }

    /**
     * Builds an immutable {@see HudPreset} from the current builder state.
     *
     * @return HudPreset
     */
    public function build() : HudPreset{
        return new HudPreset(
            paperDoll: $this->paperDoll,
            armor: $this->armor,
            tooltips: $this->tooltips,
            touchControls: $this->touchControls,
            crosshair: $this->crosshair,
            hotbar: $this->hotbar,
            health: $this->health,
            xp: $this->xp,
            food: $this->food,
            airBubbles: $this->airBubbles,
            horseHealth: $this->horseHealth,
            statusEffects: $this->statusEffects,
            itemText: $this->itemText,
        );
    }
}

