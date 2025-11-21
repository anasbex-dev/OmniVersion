<?php

declare(strict_types=1);

namespace OmniVersion\protocol;

class PacketMapper {
    
    private array $packetMappings;
    private array $conversionRules;
    
    public function __construct() {
        $this->initializeMappings();
        $this->initializeConversionRules();
    }
    
    private function initializeMappings(): void {
        $this->packetMappings = [
            // Entity packets
            'add_entity' => [
                '1.21.10' => 0x0f,
                '1.21.20' => 0x0f,
                '1.21.30' => 0x10
            ],
            'set_entity_data' => [
                '1.21.10' => 0x27,
                '1.21.20' => 0x27,
                '1.21.30' => 0x28
            ],
            'add_player' => [
                '1.21.10' => 0x02,
                '1.21.20' => 0x02,
                '1.21.30' => 0x03
            ]
        ];
    }
    
    private function initializeConversionRules(): void {
        $this->conversionRules = [
            'add_player' => [
                '1.21.10_to_1.21.30' => function($packet) {
                    // Convert AddPlayer packet from 1.21.10 to 1.21.30 format
                    if (isset($packet->uuid)) {
                        // Handle UUID format changes
                        $packet->uuid = $this->convertUUIDFormat($packet->uuid);
                    }
                    return $packet;
                }
            ],
            'set_entity_data' => [
                '1.21.20_to_1.21.10' => function($packet) {
                    // Remove new metadata fields for older clients
                    if (isset($packet->metadata[100])) {
                        unset($packet->metadata[100]); // Remove new field
                    }
                    return $packet;
                }
            ]
        ];
    }
    
    public function getPacketId(string $packetName, string $version): ?int {
        return $this->packetMappings[$packetName][$version] ?? null;
    }
    
    public function convertPacket($packet, string $fromVersion, string $toVersion): ?object {
        $packetName = $this->getPacketName($packet);
        
        if (!$packetName) {
            return null;
        }
        
        $conversionKey = "{$fromVersion}_to_{$toVersion}";
        $conversionRule = $this->conversionRules[$packetName][$conversionKey] ?? null;
        
        if ($conversionRule && is_callable($conversionRule)) {
            return $conversionRule($packet);
        }
        
        // Default: return packet as-is (may cause issues)
        return $packet;
    }
    
    private function getPacketName($packet): ?string {
        $class = get_class($packet);
        $parts = explode('\\', $class);
        $className = end($parts);
        
        return strtolower(str_replace('Packet', '', $className));
    }
    
    private function convertUUIDFormat(string $uuid): string {
        // Handle UUID format conversions between versions
        return str_replace('-', '', $uuid);
    }
    
    public function getSupportedPackets(): array {
        return array_keys($this->packetMappings);
    }
}