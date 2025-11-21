<?php

declare(strict_types=1);

namespace OmniVersion\protocol;

class VersionTable {
    
    private array $protocolMap;
    private array $featureMatrix;
    
    public function __construct() {
        $this->initializeProtocolMap();
        $this->initializeFeatureMatrix();
    }
    
    private function initializeProtocolMap(): void {
        $this->protocolMap = [
            // 1.21.x Series
            675 => "1.21.0",
            676 => "1.21.1",
            677 => "1.21.2", 
            678 => "1.21.3",
            679 => "1.21.4",
            680 => "1.21.5",
            681 => "1.21.6",
            682 => "1.21.7",
            683 => "1.21.8",
            684 => "1.21.9",
            685 => "1.21.10",
            686 => "1.21.11",
            687 => "1.21.12",
            688 => "1.21.13",
            689 => "1.21.14",
            690 => "1.21.15",
            691 => "1.21.20",
            692 => "1.21.21",
            693 => "1.21.22",
            694 => "1.21.23",
            695 => "1.21.24",
            696 => "1.21.25",
            697 => "1.21.26",
            698 => "1.21.27",
            699 => "1.21.28",
            700 => "1.21.29",
            701 => "1.21.30"
        ];
    }
    
    private function initializeFeatureMatrix(): void {
        $this->featureMatrix = [
            "1.21.10" => [
                'crafter_block' => false,
                'new_entities' => ['minecraft:breeze', 'minecraft:armadillo'],
                'packet_changes' => ['add_player', 'set_entity_data'],
                'supported' => true
            ],
            "1.21.20" => [
                'crafter_block' => true,
                'new_entities' => ['minecraft:breeze', 'minecraft:armadillo', 'minecraft:bogged'],
                'packet_changes' => ['add_player', 'set_entity_data', 'inventory_transaction'],
                'supported' => true
            ],
            "1.21.30" => [
                'crafter_block' => true,
                'new_entities' => ['minecraft:breeze', 'minecraft:armadillo', 'minecraft:bogged', 'minecraft:wind_charge'],
                'packet_changes' => ['add_player', 'set_entity_data', 'inventory_transaction', 'crafting_data'],
                'supported' => true
            ]
        ];
    }
    
    public function isProtocolSupported(int $protocol): bool {
        return isset($this->protocolMap[$protocol]) && 
               $this->isVersionSupported($this->protocolMap[$protocol]);
    }
    
    public function isVersionSupported(string $version): bool {
        $features = $this->getVersionFeatures($version);
        return $features['supported'] ?? false;
    }
    
    public function getVersionName(int $protocol): ?string {
        return $this->protocolMap[$protocol] ?? null;
    }
    
    public function getProtocolVersion(string $version): ?int {
        return array_search($version, $this->protocolMap) ?: null;
    }
    
    public function getVersionFeatures(string $version): array {
        // Find the closest version features
        foreach ($this->featureMatrix as $featureVersion => $features) {
            if (version_compare($version, $featureVersion, '>=')) {
                return $features;
            }
        }
        
        return $this->featureMatrix["1.21.10"] ?? ['supported' => false];
    }
    
    public function getSupportedVersions(): array {
        return array_filter($this->protocolMap, function($version) {
            return $this->isVersionSupported($version);
        });
    }
    
    public function getProtocolRange(): array {
        $supportedProtocols = array_keys($this->getSupportedVersions());
        return [
            'min' => min($supportedProtocols),
            'max' => max($supportedProtocols)
        ];
    }
}