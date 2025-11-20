<?php

namespace OmniVersion\protocol;

class PacketMapper {

    /**
     * Data mapping per protokol Bedrock.
     * Silakan tambahkan sesuai versi baru.
     */
    public static function map(int $clientProtocol) : array {

        // Contoh mapping minimal
        return [
            "MovePlayerPacket" => self::getMovePlayerMapping($clientProtocol),
            "LevelSoundEventPacket" => self::getSoundEventMapping($clientProtocol),
            // Tambahkan packet lain di sini
        ];
    }

    /**
     * Mapping untuk MovePlayerPacket
     */
    private static function getMovePlayerMapping(int $protocol) : array {

        if ($protocol >= 671) {
            // Format terbaru
            return [
                "x" => 0,
                "y" => 1,
                "z" => 2,
                "yaw" => 3,
                "pitch" => 4
            ];
        }

        if ($protocol >= 649) {
            // Format 1.20.70 – 1.20.80
            return [
                "x" => 0,
                "y" => 1,
                "z" => 2,
                "unknown" => 3
            ];
        }

        // Format lama
        return [
            "x" => 0,
            "y" => 1,
            "z" => 2
        ];
    }

    /**
     * Contoh mapping untuk LevelSoundEventPacket
     */
    private static function getSoundEventMapping(int $protocol) : array {

        if ($protocol >= 671) {
            return [
                "soundId" => 0,
                "positionX" => 1,
                "positionY" => 2,
                "positionZ" => 3,
                "extraData" => 4
            ];
        }

        return [
            "sound" => 0,
            "posX" => 1,
            "posY" => 2,
            "posZ" => 3
        ];
    }
}