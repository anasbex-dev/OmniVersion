<?php

declare(strict_types=1);

namespace OmniVersion;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\utils\Config;
use OmniVersion\protocol\VersionTable;
use OmniVersion\protocol\PacketMapper;
use OmniVersion\Translator;
use utils\Logger;

class Main extends PluginBase implements Listener {
    
    private VersionTable $versionTable;
    private PacketMapper $packetMapper;
    private Translator $translator;
    private Logger $logger;
    private Config $config;
    
    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();
        
        // Initialize components
        $this->versionTable = new VersionTable();
        $this->packetMapper = new PacketMapper();
        $this->translator = new Translator($this->packetMapper);
        $this->logger = new Logger($this->getDataFolder() . "omni_version.log");
        
        // Register events
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        
        $this->logger->info("OmniVersion v" . $this->getDescription()->getVersion() . " enabled");
        $this->logger->info("Supported versions: " . implode(", ", $this->versionTable->getSupportedVersions()));
        
        if ($this->config->get("enable-metrics", true)) {
            $this->startMetricsCollection();
        }
    }
    
    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        $origin = $event->getOrigin();
        
        if ($packet instanceof LoginPacket) {
            $this->handleClientLogin($packet, $origin);
        }
    }
    
    private function handleClientLogin(LoginPacket $packet, $origin): void {
        $protocol = $packet->protocol;
        $clientVersion = $packet->clientData["GameVersion"] ?? "Unknown";
        $clientId = $packet->clientData["ClientRandomId"] ?? 0;
        
        $this->logger->info("Client connection - Version: $clientVersion, Protocol: $protocol, ClientId: $clientId");
        
        if ($this->versionTable->isProtocolSupported($protocol)) {
            $mappedVersion = $this->versionTable->getVersionName($protocol);
            $this->logger->info("Protocol $protocol mapped to: $mappedVersion");
            
            // Store version info for this session
            $origin->omniVersionData = [
                'protocol' => $protocol,
                'version' => $mappedVersion,
                'client_id' => $clientId
            ];
            
        } else {
            $this->handleUnsupportedVersion($protocol, $origin);
        }
    }
    
    public function onPlayerLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();
        $session = $player->getNetworkSession();
        
        if (isset($session->omniVersionData)) {
            $versionData = $session->omniVersionData;
            $player->sendMessage("§aOmniVersion: Connected with " . $versionData['version']);
            
            $this->logger->info("Player " . $player->getName() . " logged in with " . $versionData['version']);
        }
    }
    
    private function handleUnsupportedVersion(int $protocol, $origin): void {
        $action = $this->config->get("unsupported-version-action", "kick");
        
        switch ($action) {
            case "kick":
                $origin->disconnect("Unsupported client version. Please use supported versions.");
                break;
            case "warn":
                $this->logger->warning("Unsupported protocol $protocol - Allowing connection with limitations");
                break;
            case "allow":
                $this->logger->info("Unsupported protocol $protocol - Allowing connection");
                break;
        }
    }
    
    private function startMetricsCollection(): void {
        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends \pocketmine\scheduler\Task {
            private Main $plugin;
            
            public function __construct(Main $plugin) {
                $this->plugin = $plugin;
            }
            
            public function onRun(): void {
                $this->plugin->collectMetrics();
            }
        }, 1200); // Every minute
    }
    
    public function collectMetrics(): void {
        $versionCounts = [];
        $players = $this->getServer()->getOnlinePlayers();
        
        foreach ($players as $player) {
            $session = $player->getNetworkSession();
            if (isset($session->omniVersionData)) {
                $version = $session->omniVersionData['version'];
                $versionCounts[$version] = ($versionCounts[$version] ?? 0) + 1;
            }
        }
        
        $this->logger->debug("Version metrics: " . json_encode($versionCounts));
    }
    
    public function getVersionTable(): VersionTable {
        return $this->versionTable;
    }
    
    public function getTranslator(): Translator {
        return $this->translator;
    }
    
    public function getPacketMapper(): PacketMapper {
        return $this->packetMapper;
    }
}