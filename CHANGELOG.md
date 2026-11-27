# Changelog

## 2.0.0 (2026-11-27)

The Poggit plugin version for the original [pmmp/PocketMine-MP](https://github.com/pmmp/PocketMine-MP) 5.x is kept on the
[`legacy/pmmp-5.44`](https://github.com/presentkim-pm/CameraAPI/tree/legacy/pmmp-5.44) branch.

### ⚠️ Breaking changes
- Targets [axolotl-pm/PocketMine-MP](https://github.com/axolotl-pm/PocketMine-MP) `5.51` (BedrockProtocol 64,
  Bedrock `1.26.60`) and **PHP 8.4+**.
- Distributed as a phar from GitHub Releases instead of Poggit.
- Camera ease types are now the `CameraSetInstructionEaseType` enum instead of ints, because the protocol changed it
  to a string-backed enum:
  - `CameraSetBuilder::ease()`, `CameraFovBuilder::ease()`, `CameraSplineBuilder::ease()`,
    `CameraSplineBuilder::addRotation()`, `CameraSplineBuilder::addProgress()` and
    `CameraMarker::applyToSession()` take a `CameraSetInstructionEaseType` instead of an `int`.
  - In timeline JSON, `ease.type` is the vanilla ease name, or the ordinal of the enum case (`0` = `linear`, ...).
    An unknown name or ordinal now throws `InvalidArgumentException` when the timeline is parsed.
- `CameraPresetBuilder::createDirect()` has two new trailing parameters, `applyInheritedStartingRotation` and
  `startingRotation`, matching the Bedrock 1.26.60 `CameraPreset`.

### ✨ Features
- `CameraPresetBuilder::setApplyInheritedStartingRotation()` and `setStartingRotation()` for the new camera preset fields.
- PHPUnit tests that build every packet through the plugin's builders and registries and encode / decode them with the
  real BedrockProtocol serializer.

### 🔄 Changes
- The camera marker's fake player is spawned with the BedrockProtocol 64 `PlayerListPacket` / `AddPlayerPacket` API.
- `composer.json` added (Composer is only used for development and testing); `plugin.yml` has
  `src-namespace-prefix`.

### 🐛 Fixes
- The `ease` of `set` and `fov` timeline steps was silently ignored: the parsed ease was never passed into the step.
