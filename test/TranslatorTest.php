<?php

declare(strict_types=1);

namespace OmniVersion\tests;

use OmniVersion\Translator;
use OmniVersion\protocol\PacketMapper;
use pocketmine\network\mcpe\protocol\TextPacket;
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase {
    
    private Translator $translator;
    private PacketMapper $packetMapper;
    
    protected function setUp(): void {
        $this->packetMapper = new PacketMapper();
        $this->translator = new Translator($this->packetMapper);
    }
    
    public function testPacketTranslationSameVersion(): void {
        $packet = new TextPacket();
        $packet->type = TextPacket::TYPE_RAW;
        $packet->message = "Test message";
        
        $result = $this->translator->translateOutgoing($packet, "1.21.20");
        
        $this->assertSame($packet, $result, "Packet should be unchanged for same version");
    }
    
    public function testEntityDataPacketTranslation(): void {
        // Mock an entity data packet
        $packet = $this->createMock(\pocketmine\network\mcpe\protocol\SetEntityDataPacket::class);
        $packet->metadata = [1 => "value1", 100 => "new_field"];
        
        $result = $this->translator->translateOutgoing($packet, "1.21.10");
        
        $this->assertInstanceOf(\pocketmine\network\mcpe\protocol\SetEntityDataPacket::class, $result);
    }
    
    public function testTranslationCache(): void {
        $packet = new TextPacket();
        $packet->type = TextPacket::TYPE_RAW;
        $packet->message = "Cache test";
        
        // First translation
        $result1 = $this->translator->translateOutgoing($packet, "1.21.10");
        
        // Second translation (should use cache)
        $result2 = $this->translator->translateOutgoing($packet, "1.21.10");
        
        $this->assertSame($result1, $result2, "Cached translation should return same object");
    }
    
    public function testClearCache(): void {
        $packet = new TextPacket();
        $packet->type = TextPacket::TYPE_RAW;
        $packet->message = "Cache clear test";
        
        $this->translator->translateOutgoing($packet, "1.21.10");
        
        // Reflection to access private cache
        $reflection = new \ReflectionClass($this->translator);
        $cacheProperty = $reflection->getProperty('translationCache');
        $cacheProperty->setAccessible(true);
        
        $cacheBefore = $cacheProperty->getValue($this->translator);
        $this->assertNotEmpty($cacheBefore, "Cache should not be empty after translation");
        
        $this->translator->clearCache();
        
        $cacheAfter = $cacheProperty->getValue($this->translator);
        $this->assertEmpty($cacheAfter, "Cache should be empty after clear");
    }
}