<?php

require __DIR__ . "/../src/OmniVersion/protocol/Translator.php";
require __DIR__ . "/../src/OmniVersion/protocol/PacketMapper.php";
require __DIR__ . "/../src/OmniVersion/protocol/VersionTable.php";

use OmniVersion\protocol\Translator;
use OmniVersion\protocol\VersionTable;
use pocketmine\network\mcpe\protocol\DataPacket;

/**
 * Dummy packet class untuk test
 */
class DummyPacket extends DataPacket {
    public int $value = 0;

    public function pid() : int { return 999; }

    protected function decodePayload() : void {}
    protected function encodePayload() : void {}
}


echo "=== OmniVersion Unit Test ===\n";

$translator = new Translator();

// ----- Test 1: translateIn basic -----
$packet = new DummyPacket();
$packet->value = 10;

$translated = $translator->translateIn($packet, 594);

if ($translated instanceof DummyPacket) {
    echo "[OK] translateIn() returned correct packet\n";
} else {
    echo "[FAIL] translateIn() returned wrong packet type\n";
}

// ----- Test 2: Version table -----
if (VersionTable::isSupported(671)) {
    echo "[OK] Protocol 671 supported\n";
} else {
    echo "[FAIL] Protocol 671 not detected\n";
}

if (VersionTable::getVersionName(649) === "1.20.70") {
    echo "[OK] VersionTable mapping correct for 649\n";
} else {
    echo "[FAIL] VersionTable mapping mismatch\n";
}

// ----- Test Complete -----
echo "=== Test selesai ===\n";