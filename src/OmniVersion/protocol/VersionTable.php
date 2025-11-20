<?php

namespace OmniVersion\protocol;

class VersionTable {

    /**
     * Mapping protocol → version name
     * (Hanya versi yang benar-benar dipakai di project)
     */
    public static function getSupportedVersions(): array {
        return [
            // === Stable Releases Supported ===
            649 => "1.20.70",
            662 => "1.20.80",

            671 => "1.21.0",
            680 => "1.21.10",

            // Tambahan opsional (boleh hapus jika tidak pakai)
            691 => "1.21.30",
            640 => "1.20.60",
            630 => "1.20.50",
            622 => "1.20.40",
            618 => "1.20.30",
            614 => "1.20.0",
            594 => "1.19.80",
            589 => "1.19.70",
            582 => "1.19.60",
        ];
    }

    /** Cek apakah protocol didukung */
    public static function isSupported(int $protocol): bool {
        return isset(self::getSupportedVersions()[$protocol]);
    }

    /** Ambil nama versi berdasarkan protocol */
    public static function getVersionName(int $protocol): ?string {
        $list = self::getSupportedVersions();
        return $list[$protocol] ?? null;
    }

    /** Cari protocol berdasarkan partial name */
    public static function findProtocolsByName(string $name): array {
        $res = [];
        foreach (self::getSupportedVersions() as $protocol => $vname) {
            if (strpos($vname, $name) !== false) {
                $res[] = $protocol;
            }
        }
        return $res;
    }

    // ===== Custom Runtime Support =====

    private static array $custom = [];

    public static function addCustom(int $protocol, string $name): void {
        self::$custom[$protocol] = $name;
    }

    public static function getAll(): array {
        return array_merge(self::getSupportedVersions(), self::$custom);
    }

    public static function getVersionNameSafe(int $protocol): string {
        return self::getAll()[$protocol] ?? ("protocol:" . $protocol);
    }
}