<?php

namespace OmniVersion\protocol;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use OmniVersion\utils\Logger;

/**
 * Translator: melakukan transformasi paket IN/OUT agar kompatibel antara versi.
 *
 * Pendekatan:
 * - translateIn: client -> server
 * - translateOut: server -> client
 *
 * Fokus utama:
 * - StartGamePacket (complete/defensive handling)
 * - MovePlayerPacket
 * - AddActor/AddPlayer
 * - LevelChunkPacket (stub + placeholder simplifier)
 * - InventoryTransactionPacket (safeguard + basic transform)
 *
 * NOTE: implementasi ini bersifat pragmatic & defensive — beberapa transform
 * memerlukan mapping runtime id / registry yang harus diisi berdasarkan data
 * asli dari versi Bedrock tertentu. Bagian TODO jelas ditandai.
 */
class Translator {

    private bool $debug;

    public function __construct(bool $debug = false) {
        $this->debug = $debug;
    }

    /**
     * Translate incoming packets (client -> server)
     */
    public function translateIn(DataPacket $packet, int $clientProtocol) : DataPacket {
        $vname = VersionTable::getVersionNameSafe($clientProtocol);

        if ($this->debug) Logger::debug("[Translator][In] " . get_class($packet) . " -> " . $vname);

        switch (true) {
            case $packet instanceof StartGamePacket:
                return $this->translateInStartGame($packet, $clientProtocol, $vname);

            case $packet instanceof MovePlayerPacket:
                return $this->translateInMovePlayer($packet, $clientProtocol, $vname);

            case $packet instanceof InventoryTransactionPacket:
                return $this->translateInInventoryTransaction($packet, $clientProtocol, $vname);

            // For AddPlayer/AddActor client->server is less common but keep defensive handlers
            case $packet instanceof AddActorPacket:
            case $packet instanceof AddPlayerPacket:
                return $this->translateInAddActorOrPlayer($packet, $clientProtocol, $vname);

            default:
                return $packet;
        }
    }

    /**
     * Translate outgoing packets (server -> client)
     */
    public function translateOut(DataPacket $packet, int $clientProtocol) : DataPacket {
        $vname = VersionTable::getVersionNameSafe($clientProtocol);

        if ($this->debug) Logger::debug("[Translator][Out] " . get_class($packet) . " -> " . $vname);

        switch (true) {
            case $packet instanceof StartGamePacket:
                return $this->translateOutStartGame($packet, $clientProtocol, $vname);

            case $packet instanceof MovePlayerPacket:
                return $this->translateOutMovePlayer($packet, $clientProtocol, $vname);

            case $packet instanceof LevelChunkPacket:
                return $this->translateOutLevelChunk($packet, $clientProtocol, $vname);

            case $packet instanceof AddActorPacket:
            case $packet instanceof AddPlayerPacket:
                return $this->translateOutAddActorOrPlayer($packet, $clientProtocol, $vname);

            case $packet instanceof InventoryTransactionPacket:
                return $this->translateOutInventoryTransaction($packet, $clientProtocol, $vname);

            default:
                return $packet;
        }
    }

    // ---------------------------
    // StartGamePacket (IN / OUT)
    // ---------------------------

    private function translateInStartGame(StartGamePacket $pkt, int $clientProtocol, string $vname) : StartGamePacket {
        // Client rarely sends StartGamePacket (server -> client usually). Defensive normalize.
        if ($this->debug) Logger::debug("[Translator][In][StartGame] Defensive handling for " . $vname);

        // Ensure worldName exists
        if (!property_exists($pkt, 'worldName') || $pkt->worldName === null) {
            $pkt->worldName = "OmniVersionWorld";
            if ($this->debug) Logger::info("[Translator] Added default worldName for incoming StartGamePacket");
        }

        // Normalize runtime / dimension codec presence
        if (!property_exists($pkt, 'dimension_codec')) {
            $pkt->dimension_codec = $this->defaultDimensionCodec();
            if ($this->debug) Logger::info("[Translator] Injected default dimension_codec for incoming StartGamePacket");
        }

        return $pkt;
    }

    private function translateOutStartGame(StartGamePacket $pkt, int $clientProtocol, string $vname) : StartGamePacket {
        // Server -> client: we must adapt StartGame payload shape to client expectations.
        if ($this->debug) Logger::debug("[Translator][Out][StartGame] preparing for " . $vname);

        // 1) Ensure required base fields are present for older clients
        if (!property_exists($pkt, 'worldName')) {
            $pkt->worldName = "OmniVersionWorld";
            if ($this->debug) Logger::info("[Translator] Added worldName for StartGamePacket");
        }

        // 2) Remove unknown/new fields not supported by older versions
        // Example: 'experimentalGameplay' introduced in newer builds
        if (property_exists($pkt, 'experimentalGameplay') && $this->isClientOlderThan($clientProtocol, '1.20.80')) {
            unset($pkt->experimentalGameplay);
            if ($this->debug) Logger::info("[Translator] Removed experimentalGameplay for older client " . $vname);
        }

        // 3) Dimension codec adaptation
        if (property_exists($pkt, 'dimension_codec')) {
            if ($this->isClientOlderThan($clientProtocol, '1.20.80')) {
                // simplify complex codec for older client (placeholder: developer should implement mapping)
                $pkt->dimension_codec = $this->simplifyDimensionCodec($pkt->dimension_codec, $clientProtocol);
                if ($this->debug) Logger::info("[Translator] Simplified dimension_codec for " . $vname);
            } else {
                // For newer clients, ensure codec contains required keys
                $pkt->dimension_codec = $this->ensureDimensionCodecComplete($pkt->dimension_codec);
            }
        } else {
            // if missing, inject safe default
            $pkt->dimension_codec = $this->defaultDimensionCodec();
            if ($this->debug) Logger::info("[Translator] Injected default dimension_codec for " . $vname);
        }

        // 4) Runtime IDs / block palette adjustments
        if (property_exists($pkt, 'block_palette')) {
            // If the client is older, shrink palette to supported subset
            if ($this->isClientOlderThan($clientProtocol, '1.21.10')) {
                $pkt->block_palette = $this->shrinkBlockPaletteForClient($pkt->block_palette, $clientProtocol);
                if ($this->debug) Logger::info("[Translator] Shrunk block_palette for " . $vname);
            }
        }

        // 5) Ensure player permissions / game rules exist
        if (!property_exists($pkt, 'player_game_type')) {
            $pkt->player_game_type = 1; // survival default
            if ($this->debug) Logger::info("[Translator] Added default player_game_type");
        }

        // 6) Map entity runtime IDs if needed (server may use different registry)
        if (property_exists($pkt, 'entity_runtime_id_map')) {
            // map runtime ids (developer should populate mapping rules)
            $pkt->entity_runtime_id_map = $this->mapRuntimeIdsForClient($pkt->entity_runtime_id_map, $clientProtocol);
            if ($this->debug) Logger::info("[Translator] Mapped entity runtime IDs for " . $vname);
        }

        // 7) Final safety: remove properties that unknown older clients could reject
        $pkt = $this->stripUnsupportedStartGameFields($pkt, $clientProtocol);

        return $pkt;
    }

    // ---------------------------
    // MovePlayerPacket (IN / OUT)
    // ---------------------------

    private function translateInMovePlayer(MovePlayerPacket $pkt, int $clientProtocol, string $vname) : MovePlayerPacket {
        if ($this->debug) Logger::debug("[Translator][In][MovePlayer] -> " . $vname);

        // Ensure yaw/pitch exist
        if (!property_exists($pkt, 'yaw')) $pkt->yaw = 0.0;
        if (!property_exists($pkt, 'pitch')) $pkt->pitch = 0.0;

        // Example: some older clients send position scaled differently — convert if needed
        // TODO: populate exact scale rules if found.
        return $pkt;
    }

    private function translateOutMovePlayer(MovePlayerPacket $pkt, int $clientProtocol, string $vname) : MovePlayerPacket {
        if ($this->debug) Logger::debug("[Translator][Out][MovePlayer] -> " . $vname);

        // Normalize numeric types
        $pkt->yaw = $this->ensureFloat($pkt->yaw);
        $pkt->pitch = $this->ensureFloat($pkt->pitch);

        // If older client expects fewer fields, remove extras (defensive)
        // (Example: some versions had 'mode' enums difference)
        if (property_exists($pkt, 'mode') && $this->isClientOlderThan($clientProtocol, '1.20.80')) {
            // map unknown mode values to supported set
            if ($pkt->mode === 3) $pkt->mode = 2;
        }

        return $pkt;
    }

    // ---------------------------
    // AddActor / AddPlayer
    // ---------------------------

    private function translateInAddActorOrPlayer(DataPacket $pkt, int $clientProtocol, string $vname) : DataPacket {
        if ($this->debug) Logger::debug("[Translator][In][AddActor/AddPlayer] -> " . $vname);

        // Defensive mapping: ensure runtime entity ids consistent
        if (property_exists($pkt, 'entityRuntimeId')) {
            $pkt->entityRuntimeId = $this->mapIncomingRuntimeId($pkt->entityRuntimeId, $clientProtocol);
        }

        // Ensure metadata is present
        if (property_exists($pkt, 'metadata') && !is_array($pkt->metadata)) {
            $pkt->metadata = (array) $pkt->metadata;
        }

        return $pkt;
    }

    private function translateOutAddActorOrPlayer(DataPacket $pkt, int $clientProtocol, string $vname) : DataPacket {
        if ($this->debug) Logger::debug("[Translator][Out][AddActor/AddPlayer] -> " . $vname);

        // Map server runtime id to client runtime id space if mapping differs
        if (property_exists($pkt, 'entityRuntimeId')) {
            $pkt->entityRuntimeId = $this->mapOutgoingRuntimeId($pkt->entityRuntimeId, $clientProtocol);
        }

        // Simplify metadata keys for older clients
        if (property_exists($pkt, 'metadata') && is_array($pkt->metadata) && $this->isClientOlderThan($clientProtocol, '1.21.10')) {
            $pkt->metadata = $this->shrinkEntityMetadataForClient($pkt->metadata, $clientProtocol);
        }

        return $pkt;
    }

    // ---------------------------
    // LevelChunkPacket (OUT) - heavy; provide safe stubs
    // ---------------------------

    private function translateOutLevelChunk(LevelChunkPacket $pkt, int $clientProtocol, string $vname) : LevelChunkPacket {
        if ($this->debug) Logger::debug("[Translator][Out][LevelChunk] -> " . $vname);

        // Level chunk encoding changes are complex (section layouts, palettes).
        // Here we provide safe behaviors:
        // - If client older, optionally strip newer sub-chunks
        // - If block palette contains unknown runtime IDs, map them or fallback to air (0)
        if (property_exists($pkt, 'chunk')) {
            // TODO: implement real chunk mapping: decode, remap block runtime IDs, re-encode.
            // For now, ensure chunk exists and log if palette contains unknown IDs.
            $pkt->chunk = $this->remapChunkForClient($pkt->chunk, $clientProtocol);
        }

        return $pkt;
    }

    // ---------------------------
    // InventoryTransactionPacket
    // ---------------------------

    private function translateInInventoryTransaction(InventoryTransactionPacket $pkt, int $clientProtocol, string $vname) : InventoryTransactionPacket {
        if ($this->debug) Logger::debug("[Translator][In][InventoryTransaction] -> " . $vname);

        // Inventory transaction structures differ by version. Basic safety:
        // - Ensure actions array exists
        if (!property_exists($pkt, 'actions')) {
            $pkt->actions = [];
        }

        // TODO: map action structure differences (slot indexes, legacy flags)
        return $pkt;
    }

    private function translateOutInventoryTransaction(InventoryTransactionPacket $pkt, int $clientProtocol, string $vname) : InventoryTransactionPacket {
        if ($this->debug) Logger::debug("[Translator][Out][InventoryTransaction] -> " . $vname);

        // Ensure items / actions sanitized for older clients
        if (property_exists($pkt, 'actions') && is_array($pkt->actions) && $this->isClientOlderThan($clientProtocol, '1.20.80')) {
            // apply sanitizer for old clients
            $pkt->actions = $this->sanitizeInventoryActions($pkt->actions, $clientProtocol);
        }

        return $pkt;
    }

    // ---------------------------
    // Helpers & Utilities
    // ---------------------------

    private function isClientOlderThan(int $protocol, string $versionName) : bool {
        $list = VersionTable::getSupportedVersions();
        $target = null;
        foreach ($list as $p => $v) {
            if ($v === $versionName || strpos($v, $versionName) !== false) {
                $target = $p;
                break;
            }
        }
        if ($target === null) return false;
        return $protocol < $target;
    }

    private function ensureFloat($val) : float {
        if (is_float($val)) return $val;
        if (is_int($val)) return (float)$val;
        if (is_string($val)) return (float)$val;
        return 0.0;
    }

    private function defaultDimensionCodec() {
        // Minimal safe codec placeholder
        return [
            "minecraft:dimension_type" => [
                "type" => "default",
                "data" => []
            ]
        ];
    }

    private function ensureDimensionCodecComplete($codec) {
        // Ensure codec has required keys (placeholder)
        if (!is_array($codec)) return $this->defaultDimensionCodec();
        return $codec;
    }

    private function simplifyDimensionCodec($codec, int $clientProtocol) {
        // Placeholder: real simplification needs understanding of codec structure.
        // Return a minimal codec that older clients accept.
        return $this->defaultDimensionCodec();
    }

    private function shrinkBlockPaletteForClient($palette, int $clientProtocol) {
        // Palette is typically an array of block entries.
        // Implement conservative shrink: keep only entries with known runtime IDs,
        // else replace unknown with "air".
        if (!is_array($palette)) return $palette;

        $mapped = [];
        foreach ($palette as $entry) {
            if ($this->isRuntimeIdSupported($entry, $clientProtocol)) {
                $mapped[] = $entry;
            } else {
                // fallback to air entry (placeholder)
                $mapped[] = $this->airPaletteEntry();
            }
        }
        return $mapped;
    }

    private function airPaletteEntry() {
        // Minimal representation for air block in palette
        return ["name" => "minecraft:air", "runtime_id" => 0];
    }

    private function isRuntimeIdSupported($entry, int $clientProtocol) : bool {
        // Placeholder: developer must implement mapping against client's registry.
        // For now assume true for common blocks.
        return true;
    }

    private function mapRuntimeIdsForClient($map, int $clientProtocol) {
        // Map entity runtime id registry for client — placeholder returns original.
        return $map;
    }

    private function mapIncomingRuntimeId($id, int $clientProtocol) {
        // Map incoming runtime id (client -> server) if needed
        return $id;
    }

    private function mapOutgoingRuntimeId($id, int $clientProtocol) {
        // Map server runtime id -> client runtime id for compatibility
        return $id;
    }

    private function shrinkEntityMetadataForClient(array $metadata, int $clientProtocol) : array {
        // Keep only metadata keys known to older clients (placeholder)
        return $metadata;
    }

    private function remapChunkForClient($chunkData, int $clientProtocol) {
        // Placeholder: chunk decoding & re-encoding is heavy.
        // For now simply return chunkData; developers should implement full remap.
        if ($this->debug) Logger::info("[Translator] remapChunkForClient called (stub) for protocol " . $clientProtocol);
        return $chunkData;
    }

    private function sanitizeInventoryActions(array $actions, int $clientProtocol) : array {
        // Remove unknown action types for older clients
        // Placeholder: return same actions
        return $actions;
    }

    private function stripUnsupportedStartGameFields(StartGamePacket $pkt, int $clientProtocol) : StartGamePacket {
        // Remove fields unknown to older client versions to avoid rejection
        // Example: hypothetical 'extra_modern_field'
        if ($this->isClientOlderThan($clientProtocol, '1.21.10')) {
            if (property_exists($pkt, 'extra_modern_field')) {
                unset($pkt->extra_modern_field);
                if ($this->debug) Logger::info("[Translator] Stripped extra_modern_field for client " . $clientProtocol);
            }
        }
        return $pkt;
    }

    private function mapRuntimeIdsForClientSimple(array $map, int $clientProtocol) : array {
        // Simple identity mapping by default
        return $map;
    }
}