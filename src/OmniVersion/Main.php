<?php

namespace OmniVersion;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;

use OmniVersion\protocol\Translator;
use OmniVersion\protocol\VersionTable;
use OmniVersion\utils\Logger;

class Main extends PluginBase implements Listener {

    private Translator $translator;
    private bool $debug = false;
    private bool $logPackets = false;

    public function onEnable() : void {
        // load config (fallback to defaults)
        $cfgPath = $this->getDataFolder() . "config.yml";
        if (!file_exists($cfgPath)) {
            @mkdir($this->getDataFolder());
            // copy default config from plugin root if exists
            $defaultConfig = $this->getFile() ?? null;
            // create minimal default
            $default = [
                "debug" => false,
                "log_packets" => false,
                "auto_update_version" => true
            ];
            $config = new Config($cfgPath, Config::YAML, $default);
        } else {
            $config = new Config($cfgPath, Config::YAML);
        }

        $this->debug = (bool)$config->get("debug", false);
        $this->logPackets = (bool)$config->get("log_packets", false);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->translator = new Translator($this->debug);

        $this->getLogger()->info(C::GREEN . "OmniVersion enabled! Debug: " . ($this->debug ? "ON" : "OFF"));
        $this->getLogger()->info(C::YELLOW . "Supported Protocols:");
        foreach (VersionTable::getSupportedVersions() as $protocol => $version) {
            $this->getLogger()->info(C::GRAY . "- $protocol ($version)");
        }
    }

    public function onDisable() : void {
        $this->getLogger()->info(C::RED . "OmniVersion disabled.");
    }

    /**
     * Player Join - announce protocol
     */
    public function onJoin(PlayerJoinEvent $event) : void {
        $player = $event->getPlayer();
        $protocol = $player->getNetworkSession()->getProtocolId();

        $event->setJoinMessage(
            C::GREEN . "[OmniVersion] Welcome " . C::YELLOW . $player->getName() .
            C::WHITE . " (Protocol: " . C::AQUA . $protocol . C::WHITE . ")"
        );

        $this->getLogger()->info(C::YELLOW . "Player " . $player->getName() .
            " joined with protocol " . $protocol);
    }

    /**
     * Incoming packets (client -> server)
     */
    public function onPacketReceive(DataPacketReceiveEvent $event) : void {
        $packet = $event->getPacket();
        $origin = $event->getOrigin();
        $player = $origin->getPlayer();

        if (!$player) return;

        $clientProtocol = $player->getNetworkSession()->getProtocolId();

        if ($this->logPackets) {
            $this->getLogger()->debug("Recv packet: " . get_class($packet) . " from " . $player->getName());
        }

        if ($packet instanceof LoginPacket) {
            $this->getLogger()->info(C::GRAY . "LoginPacket from " . $player->getName() .
                " (Protocol: " . $clientProtocol . ")");
        }

        $translated = $this->translator->translateIn($packet, $clientProtocol);

        if ($translated !== $packet) {
            $event->setPacket($translated);
            if ($this->debug) {
                $this->getLogger()->info("[OmniVersion][In] Translated " . get_class($packet) . " for protocol " . $clientProtocol);
            }
        }
    }

    /**
     * Outgoing packets (server -> client)
     *
     * DataPacketSendEvent contains multiple packets and targets (clients).
     * We iterate and translate per-target protocol if possible.
     */
    public function onPacketSend(DataPacketSendEvent $event) : void {
        $packets = $event->getPackets();
        $targets = $event->getTargets(); // array of NetworkSession/PlayerIDs depending on API

        // If there are no targets, nothing to do
        if (empty($targets) || empty($packets)) return;

        // For each target, detect protocol and produce translated packets for that target.
        // Note: DataPacketSendEvent API expects packets array to be the same for all targets,
        // so if different protocols exist among targets, we currently fallback to translating
        // only when there's a single target, or log debug otherwise.
        if (count($targets) === 1) {
            $target = $targets[0];
            // try to obtain Player from session
            $player = $target->getPlayer() ?? null;
            if ($player === null) return;

            $clientProtocol = $player->getNetworkSession()->getProtocolId();

            if ($this->logPackets) {
                $this->getLogger()->debug("Send packets to " . $player->getName() . " (protocol " . $clientProtocol . ")");
            }

            $newPackets = [];
            foreach ($packets as $packet) {
                $translated = $this->translator->translateOut($packet, $clientProtocol);
                $newPackets[] = $translated;
                if ($this->debug && $translated !== $packet) {
                    $this->getLogger()->info("[OmniVersion][Out] Translated " . get_class($packet) . " for protocol " . $clientProtocol);
                }
            }

            // replace packets for single-target send
            $event->setPackets($newPackets);
        } else {
            // multiple targets: we can't easily provide per-target different packet arrays using this event
            // Log debug info, and leave default behavior (future: split sends per protocol)
            if ($this->debug) {
                $this->getLogger()->warn("[OmniVersion] Multiple targets in DataPacketSendEvent — per-target translation not applied (TODO: handle batching).");
            }
        }
    }
}