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

use kim\present\cameraapi\session\CameraSession;
use pocketmine\network\mcpe\protocol\SetHudPacket;
use pocketmine\network\mcpe\protocol\types\hud\HudElement;
use pocketmine\network\mcpe\protocol\types\hud\HudVisibility;
use pocketmine\player\Player;

/**
 * Immutable description of a HUD layout.
 *
 * Each property corresponds to a specific {@see HudElement}; when true, the
 * element is intended to be visible for the preset, otherwise it will be
 * hidden when the preset is applied.
 *
 * The constructor defaults all flags to true so that you can conveniently use
 * PHP named parameters to override only the elements you want to change:
 *
 *  $cinematic = new HudPreset(
 *      paperDoll: false,
 *      armor: false,
 *      /* ... *\/
 *  );
 */
final readonly class HudPreset{

    /**
     * Maps preset property names to {@see HudElement} enum cases.
     *
     * @var array<string, HudElement>
     */
    private const HUD_ELEMENT_MAP = [
        "paperDoll" => HudElement::PAPER_DOLL,
        "armor" => HudElement::ARMOR,
        "tooltips" => HudElement::TOOLTIPS,
        "touchControls" => HudElement::TOUCH_CONTROLS,
        "crosshair" => HudElement::CROSSHAIR,
        "hotbar" => HudElement::HOTBAR,
        "health" => HudElement::HEALTH,
        "xp" => HudElement::XP,
        "food" => HudElement::FOOD,
        "airBubbles" => HudElement::AIR_BUBBLES,
        "horseHealth" => HudElement::HORSE_HEALTH,
        "statusEffects" => HudElement::STATUS_EFFECTS,
        "itemText" => HudElement::ITEM_TEXT,
    ];

    /**
     * @param bool $paperDoll     Paper doll (player model) visibility.
     * @param bool $armor         Armor bar visibility.
     * @param bool $tooltips      Tooltip visibility.
     * @param bool $touchControls Touch control hints visibility.
     * @param bool $crosshair     Crosshair visibility.
     * @param bool $hotbar        Hotbar visibility.
     * @param bool $health        Health bar visibility.
     * @param bool $xp            Experience bar visibility.
     * @param bool $food          Food bar visibility.
     * @param bool $airBubbles    Air bubbles (underwater) visibility.
     * @param bool $horseHealth   Horse health bar visibility.
     * @param bool $statusEffects Status effect icons visibility.
     * @param bool $itemText      Item name text visibility.
     */
    public function __construct(
        public bool $paperDoll = true,
        public bool $armor = true,
        public bool $tooltips = true,
        public bool $touchControls = true,
        public bool $crosshair = true,
        public bool $hotbar = true,
        public bool $health = true,
        public bool $xp = true,
        public bool $food = true,
        public bool $airBubbles = true,
        public bool $horseHealth = true,
        public bool $statusEffects = true,
        public bool $itemText = true
    ){}

    /**
     * Applies this HUD preset to the given target.
     *
     * This method:
     *  - First hides all known HUD elements.
     *  - Then re-enables the elements that are flagged as visible in this preset.
     *
     * @param CameraSession|Player $target
     */
    public function send(CameraSession|Player $target) : void{
        $player = $target instanceof CameraSession ? $target->getPlayer() : $target;
        if($player === null || !$player->isConnected()){
            return;
        }

        $session = $player->getNetworkSession();
        $allElements = array_values(self::HUD_ELEMENT_MAP);
        $visibleElements = $this->getVisibleElements();

        // Hide everything we know about
        $session->sendDataPacket(SetHudPacket::create($allElements, HudVisibility::HIDE));

        // Re-enable only the elements marked true in this preset.
        if($visibleElements !== []){
            $session->sendDataPacket(SetHudPacket::create($visibleElements, HudVisibility::RESET));
        }
    }

    /**
     * Returns the list of HUD elements that are enabled (visible) in this preset.
     *
     * @return list<HudElement>
     */
    public function getVisibleElements() : array{
        $elements = [];
        foreach(self::HUD_ELEMENT_MAP as $key => $element){
            if($this->{$key}){
                $elements[] = $element;
            }
        }
        return $elements;
    }

    /**
     * Creates a preset where only the given HUD elements are visible.
     *
     * @param list<HudElement> $visibleElements HUD elements that should be visible; all others are hidden.
     *
     * @return self
     */
    public static function fromVisibleElements(array $visibleElements) : self{
        $flags = [];
        foreach($visibleElements as $element){
            $flags[$element->value] = true;
        }

        return new self(
            paperDoll: isset($flags[HudElement::PAPER_DOLL->value]),
            armor: isset($flags[HudElement::ARMOR->value]),
            tooltips: isset($flags[HudElement::TOOLTIPS->value]),
            touchControls: isset($flags[HudElement::TOUCH_CONTROLS->value]),
            crosshair: isset($flags[HudElement::CROSSHAIR->value]),
            hotbar: isset($flags[HudElement::HOTBAR->value]),
            health: isset($flags[HudElement::HEALTH->value]),
            xp: isset($flags[HudElement::XP->value]),
            food: isset($flags[HudElement::FOOD->value]),
            airBubbles: isset($flags[HudElement::AIR_BUBBLES->value]),
            horseHealth: isset($flags[HudElement::HORSE_HEALTH->value]),
            statusEffects: isset($flags[HudElement::STATUS_EFFECTS->value]),
            itemText: isset($flags[HudElement::ITEM_TEXT->value])
        );
    }
}

