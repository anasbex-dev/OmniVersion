<?php

namespace OmniVersion\utils;

use pocketmine\utils\TextFormat as C;

class Logger {

    public static function info(string $msg) {
        echo C::GREEN . "[OmniVersion] " . $msg . PHP_EOL;
    }

    public static function warn(string $msg) {
        echo C::YELLOW . "[OmniVersion] " . $msg . PHP_EOL;
    }

    public static function error(string $msg) {
        echo C::RED . "[OmniVersion] " . $msg . PHP_EOL;
    }

    public static function debug(string $msg) {
        echo C::DARK_GRAY . "[OmniVersion][DEBUG] " . $msg . PHP_EOL;
    }
}