<?php

declare(strict_types=1);

namespace OmniVersion;

use OmniVersion\protocol\PacketMapper;
use OmniVersion\protocol\VersionTable;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketPool;

class Translator {
    
    private PacketMapper $packetMapper;
    private VersionTable $versionTable;
    private array $translationCache;
    
    public function __construct(PacketMapper $packetMapper) {
        $this->packetMapper = $packetMapper;
        $this->versionTable = new VersionTable();
        $this->translationCache = [];
    }
    
    public function translateOutgoing(Packet $packet, string $targetVersion): ?Packet {
        $cacheKey = spl_object_hash($packet) . '_' . $targetVersion;
        
        if (isset($this->translationCache[$cacheKey])) {
            return $this->translationCache[$cacheKey];
        }
        
        $sourceVersion = $this->getServerVersion();
        $translatedPacket = $this->performTranslation($packet, $sourceVersion, $targetVersion);
        
        if ($translatedPacket !== null) {
            $this->translationCache[$cacheKey] = $translatedPacket;
        }
        
        return $translatedPacket;
    }
    
    public function translateIncoming(Packet $packet, string $clientVersion): ?Packet {
        $serverVersion = $this->getServerVersion();
        
        if ($clientVersion === $serverVersion) {
            return $packet; // No translation needed
        }
        
        return $this->performTranslation($packet, $clientVersion, $serverVersion);
    }
    
    private function performTranslation(Packet $packet, string $fromVersion, string $toVersion): ?Packet {
        if ($fromVersion === $toVersion) {
            return $packet;
        }
        
        // Get conversion rule
        $converted = $this->packetMapper->convertPacket($packet, $fromVersion, $toVersion);
        
        if ($converted instanceof Packet) {
            return $converted;
        }
        
        // If no specific conversion rule, try generic approach
        return $this->genericPacketTranslation($packet, $fromVersion, $toVersion);
    }
    
    private function genericPacketTranslation(Packet $packet, string $fromVersion, string $toVersion): ?Packet {
        // Generic packet translation logic
        $packetName = $this->getPacketShortName($packet);
        
        switch ($packetName) {
            case 'SetEntityDataPacket':
                return $this->translateEntityDataPacket($packet, $fromVersion, $toVersion);
            case 'AddPlayerPacket':
                return $this->translateAddPlayerPacket($packet, $fromVersion, $toVersion);
            case 'AddEntityPacket':
                return $this->translateAddEntityPacket($packet, $fromVersion, $toVersion);
            default:
                // For unknown packets, return as-is (may cause issues)
                return $packet;
        }
    }
    
    private function translateEntityDataPacket(Packet $packet, string $fromVersion, string $toVersion): Packet {
        // Handle entity metadata differences between versions
        if (property_exists($packet, 'metadata')) {
            $metadata = $packet->metadata;
            
            // Remove metadata fields that don't exist in target version
            $targetFeatures = $this->versionTable->getVersionFeatures($toVersion);
            $sourceFeatures = $this->versionTable->getVersionFeatures($fromVersion);
            
            // Simple metadata filtering based on version
            foreach ($metadata as $key => $value) {
                if ($this->shouldRemoveMetadataField($key, $fromVersion, $toVersion)) {
                    unset($metadata[$key]);
                }
            }
            
            $packet->metadata = $metadata;
        }
        
        return $packet;
    }
    
    private function translateAddPlayerPacket(Packet $packet, string $fromVersion, string $toVersion): Packet {
        // Handle AddPlayer packet differences
        if (version_compare($fromVersion, '1.21.20', '<') && 
            version_compare($toVersion, '1.21.20', '>=')) {
            // Add new fields for newer versions
            if (property_exists($packet, 'uuid')) {
                // Ensure UUID format compatibility
                $packet->uuid = $this->normalizeUUID($packet->uuid);
            }
        }
        
        return $packet;
    }
    
    private function translateAddEntityPacket(Packet $packet, string $fromVersion, string $toVersion): Packet {
        // Handle AddEntity packet differences
        if (property_exists($packet, 'entityRuntimeId')) {
            // Ensure entity ID compatibility
            $packet->entityRuntimeId = $this->ensureEntityIdRange($packet->entityRuntimeId, $toVersion);
        }
        
        return $packet;
    }
    
    private function shouldRemoveMetadataField(int $fieldId, string $fromVersion, string $toVersion): bool {
        // Define metadata fields that were added/removed in specific versions
        $versionChanges = [
            '1.21.20' => [100, 101], // Fields added in 1.21.20
            '1.21.30' => [102, 103], // Fields added in 1.21.30
        ];
        
        foreach ($versionChanges as $version => $fields) {
            if (version_compare($toVersion, $version, '<') && in_array($fieldId, $fields)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function normalizeUUID(string $uuid): string {
        // Normalize UUID format across versions
        if (strlen($uuid) === 32) {
            // Format: 32 chars without dashes
            return substr($uuid, 0, 8) . '-' . 
                   substr($uuid, 8, 4) . '-' . 
                   substr($uuid, 12, 4) . '-' . 
                   substr($uuid, 16, 4) . '-' . 
                   substr($uuid, 20, 12);
        }
        
        return $uuid;
    }
    
    private function ensureEntityIdRange(int $entityId, string $version): int {
        // Ensure entity ID is within valid range for target version
        $maxEntityId = 0x7FFFFFFF; // Default max
        
        if (version_compare($version, '1.21.30', '>=')) {
            $maxEntityId = 0x7FFFFFFFFFFFFFFF; // Increased in newer versions
        }
        
        return $entityId & $maxEntityId;
    }
    
    private function getPacketShortName(Packet $packet): string {
        $className = get_class($packet);
        $parts = explode('\\', $className);
        return end($parts);
    }
    
    private function getServerVersion(): string {
        // Get server's base version (PMMP version)
        // This should match one of our supported versions
        return "1.21.30"; // Adjust based on your PMMP version
    }
    
    public function clearCache(): void {
        $this->translationCache = [];
    }
}