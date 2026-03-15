<?php

declare(strict_types=1);

namespace kim\present\cameraapi\aimassist;

use pocketmine\network\mcpe\protocol\CameraAimAssistPresetsPacket;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistCategory;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistCategoryPriorities;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistCategoryPriority;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistPreset;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistPresetExclusionDefinition;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistPresetItemSettings;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistPresetsPacketOperation;
use pocketmine\player\Player;
use pocketmine\Server;

final class AimAssistPresetRegistry{

    /** @var array<string, CameraAimAssistCategory> */
    private static array $categories;

    /** @var array<string, CameraAimAssistPreset> */
    private static array $presets;

    public static function init() : void{
        self::checkInit();
    }

    protected static function checkInit() : void{
        if(!isset(self::$categories, self::$presets)){
            self::$categories = [];
            self::$presets = [];
            self::setup();
        }
    }

    protected static function setup() : void{
        self::registerVanillaDefaults();
        self::registerDefaultDefaults();
        self::sendToAll();
    }

    /**
     * Register vanilla aim assist presets.
     *
     * These presets are registered in a specific order to mirror the vanilla Minecraft BE client.
     *
     * The values are derived from analyzing vanilla 'CameraAimAssistPresetsPacket' to ensure strict protocol
     * compatibility and behavior consistency.
     */
    protected static function registerVanillaDefaults() : void{
        self::registerCategoryDirect(
            identifier: DefaultAimAssistPresetIds::CATEGORY_BUCKET,
            blocks: [
                new CameraAimAssistCategoryPriority('minecraft:cauldron', 60),
                new CameraAimAssistCategoryPriority('minecraft:lava', 60),
                new CameraAimAssistCategoryPriority('minecraft:water', 60),
            ],
            defaultBlockPriority: 30,
        );
        self::registerCategoryDirect(
            identifier: DefaultAimAssistPresetIds::CATEGORY_EMPTY_HAND,
            blocks: [
                new CameraAimAssistCategoryPriority('minecraft:oak_log', 60),
                new CameraAimAssistCategoryPriority('minecraft:cherry_log', 60),
                new CameraAimAssistCategoryPriority('minecraft:birch_log', 60),
                new CameraAimAssistCategoryPriority('minecraft:spruce_log', 60),
                new CameraAimAssistCategoryPriority('minecraft:acacia_log', 60),
                new CameraAimAssistCategoryPriority('minecraft:jungle_log', 60),
                new CameraAimAssistCategoryPriority('minecraft:dark_oak_log', 60),
                new CameraAimAssistCategoryPriority('minecraft:mangrove_log', 60),
            ],
            defaultBlockPriority: 30,
        );
        self::registerCategoryDirect(
            identifier: DefaultAimAssistPresetIds::CATEGORY_DEFAULT,
            blocks: [
                new CameraAimAssistCategoryPriority('minecraft:lever', 60),
                new CameraAimAssistCategoryPriority('minecraft:oak_button', 60),
                new CameraAimAssistCategoryPriority('minecraft:birch_button', 60),
                new CameraAimAssistCategoryPriority('minecraft:spruce_button', 60),
                new CameraAimAssistCategoryPriority('minecraft:dark_oak_button', 60),
            ],
            defaultBlockPriority: 30,
        );

        self::registerPresetDirect(
            identifier: DefaultAimAssistPresetIds::MINECRAFT_DEFAULT,
            exclusionBlocks: ['minecraft:bedrock'],
            exclusionEntities: ['minecraft:arrow'],
            liquidTargetingList: [
                'minecraft:bucket',
                'minecraft:oak_boat',
                'minecraft:birch_boat',
                'minecraft:spruce_boat',
                'minecraft:jungle_boat',
                'minecraft:acacia_boat',
                'minecraft:dark_oak_boat',
                'minecraft:mangrove_boat',
                'minecraft:cherry_boat',
                'minecraft:bamboo_raft',
                'minecraft:oak_chest_boat',
                'minecraft:birch_chest_boat',
                'minecraft:spruce_chest_boat',
                'minecraft:jungle_chest_boat',
                'minecraft:acacia_chest_boat',
                'minecraft:dark_oak_chest_boat',
                'minecraft:mangrove_chest_boat',
                'minecraft:cherry_chest_boat',
                'minecraft:bamboo_chest_raft',
            ],
            itemSettings: [
                new CameraAimAssistPresetItemSettings(
                    DefaultAimAssistPresetIds::CATEGORY_BUCKET,
                    DefaultAimAssistPresetIds::CATEGORY_BUCKET
                )
            ],
            defaultItemSettings: DefaultAimAssistPresetIds::CATEGORY_DEFAULT,
            defaultHandSettings: DefaultAimAssistPresetIds::CATEGORY_EMPTY_HAND,
        );
    }

    /**
     * Register default aim assist presets for Developers.
     *
     * - ENTITY_ONLY : Supports for targeting only entity.
     */
    protected static function registerDefaultDefaults() : void{
        self::registerCategoryDirect(
            identifier: DefaultAimAssistPresetIds::CATEGORY_ENTITY_ONLY,
            defaultEntityPriority: 99,
            defaultBlockPriority: 0,
        );

        self::registerPresetDirect(
            identifier: DefaultAimAssistPresetIds::ENTITY_ONLY,
            exclusionEntities: ['minecraft:arrow'],
            defaultItemSettings: DefaultAimAssistPresetIds::CATEGORY_ENTITY_ONLY,
            defaultHandSettings: DefaultAimAssistPresetIds::CATEGORY_ENTITY_ONLY,
        );
    }

    public static function registerCategory(CameraAimAssistCategory $category) : void{
        self::checkInit();
        self::$categories[\strtolower($category->getName())] = $category;
        self::sendToAll();
    }

    /**
     * @param CameraAimAssistCategoryPriority[] $entities
     * @param CameraAimAssistCategoryPriority[] $blocks
     * @param CameraAimAssistCategoryPriority[] $blockTags
     * @param CameraAimAssistCategoryPriority[] $entityTypeFamilies
     */
    public static function registerCategoryDirect(
        string $identifier,
        array $entities = [],
        array $blocks = [],
        array $blockTags = [],
        array $entityTypeFamilies = [],
        ?int $defaultEntityPriority = null,
        ?int $defaultBlockPriority = null
    ) : void{
        self::registerCategory(new CameraAimAssistCategory(
            $identifier,
            new CameraAimAssistCategoryPriorities(
                $entities,
                $blocks,
                $blockTags,
                $entityTypeFamilies,
                $defaultEntityPriority,
                $defaultBlockPriority,
            )
        ));
    }

    public static function getCategory(string $name) : ?CameraAimAssistCategory{
        self::checkInit();
        return self::$categories[\strtolower($name)] ?? null;
    }

    /**
     * @return CameraAimAssistCategory[]
     */
    public static function getCategories() : array{
        self::checkInit();
        return \array_values(self::$categories);
    }

    public static function registerPreset(CameraAimAssistPreset $preset) : void{
        self::checkInit();
        self::$presets[\strtolower($preset->getIdentifier())] = $preset;
        self::sendToAll();
    }

    /**
     * @param string[]                            $exclusionBlocks
     * @param string[]                            $exclusionEntities
     * @param string[]                            $exclusionBlockTags
     * @param string[]                            $exclusionEntityTypeFamilies
     * @param string[]                            $liquidTargetingList
     * @param CameraAimAssistPresetItemSettings[] $itemSettings
     */
    public static function registerPresetDirect(
        string $identifier,
        array $exclusionBlocks = [],
        array $exclusionEntities = [],
        array $exclusionBlockTags = [],
        array $exclusionEntityTypeFamilies = [],
        array $liquidTargetingList = [],
        array $itemSettings = [],
        ?string $defaultItemSettings = null,
        ?string $defaultHandSettings = null
    ) : void{
        self::registerPreset(new CameraAimAssistPreset(
            $identifier,
            new CameraAimAssistPresetExclusionDefinition(
                $exclusionBlocks,
                $exclusionEntities,
                $exclusionBlockTags,
                $exclusionEntityTypeFamilies,
            ),
            $liquidTargetingList,
            $itemSettings,
            $defaultItemSettings,
            $defaultHandSettings,
        ));
    }

    public static function getPreset(string $identifier) : ?CameraAimAssistPreset{
        self::checkInit();
        return self::$presets[\strtolower($identifier)] ?? null;
    }

    /**
     * @return CameraAimAssistPreset[]
     */
    public static function getPresets() : array{
        self::checkInit();
        return \array_values(self::$presets);
    }

    public static function toProtocolCategories() : array{
        return self::getCategories();
    }

    public static function toProtocolPresets() : array{
        return self::getPresets();
    }

    public static function createCameraAimAssistPresetsPacket() : CameraAimAssistPresetsPacket{
        return CameraAimAssistPresetsPacket::create(
            self::toProtocolCategories(),
            self::toProtocolPresets(),
            CameraAimAssistPresetsPacketOperation::SET
        );
    }

    public static function sendToAll() : void{
        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            self::sendTo($player);
        }
    }

    public static function sendTo(Player $player) : void{
        $player->getNetworkSession()->sendDataPacket(self::createCameraAimAssistPresetsPacket());
    }
}

