<?php
// build.php — Build OmniVersion.phar with auto-version (git-aware, fallback bump)
declare(strict_types=1);

$pharName = "OmniVersion.phar";
$srcDir = __DIR__ . "/src";
$pluginYml = __DIR__ . "/plugin.yml";

if (!file_exists($pluginYml)) {
    echo "Error: plugin.yml not found.\n";
    exit(1);
}

function readPluginYmlVersion(string $path): string {
    $content = file_get_contents($path);
    if (preg_match('/^version:\s*([0-9]+\.[0-9]+\.[0-9]+)\s*$/m', $content, $m)) {
        return $m[1];
    }
    return "0.0.0";
}

function writePluginYmlVersion(string $path, string $newVersion): void {
    $content = file_get_contents($path);
    $content = preg_replace('/(^version:\s*)([0-9]+\.[0-9]+\.[0-9]+)(\s*$)/m', '${1}' . $newVersion . '${3}', $content, 1);
    file_put_contents($path, $content);
}

function bumpPatch(string $v): string {
    $parts = explode('.', $v);
    while (count($parts) < 3) $parts[] = '0';
    $parts[2] = (string)((int)$parts[2] + 1);
    return implode('.', $parts);
}

// Try git describe
$autoVersion = null;
exec('git describe --tags --abbrev=0 2>/dev/null', $tagOut, $tagRc);
if ($tagRc === 0 && !empty($tagOut[0])) {
    $tag = trim($tagOut[0]);
    // get commit count since tag for build metadata
    exec('git rev-list --count HEAD 2>/dev/null', $countOut, $countRc);
    $count = ($countRc === 0 && isset($countOut[0])) ? trim($countOut[0]) : '0';
    // If tag looks like vX.Y.Z or X.Y.Z, normalize
    $tagNormalized = ltrim($tag, 'vV');
    $autoVersion = $tagNormalized . "+" . $count;
} else {
    // fallback: bump patch from plugin.yml
    $current = readPluginYmlVersion($pluginYml);
    $autoVersion = bumpPatch($current);
}

// Show chosen version
echo "Using build version: " . $autoVersion . PHP_EOL;

// Update plugin.yml temporarily with new version (if it's semantic X.Y.Z)
$currentVersion = readPluginYmlVersion($pluginYml);
$tempPlugin = __DIR__ . "/.plugin.yml.tmp";
copy($pluginYml, $tempPlugin);
writePluginYmlVersion($tempPlugin, $autoVersion);

// Remove old phar if exists
if (file_exists($pharName)) unlink($pharName);

// Build phar
$phar = new Phar($pharName);
$phar->startBuffering();

// stub: run plugin main
$stub = <<<'PHP'
<?php
Phar::mapPhar('OmniVersion.phar');
require 'phar://OmniVersion.phar/OmniVersion/Main.php';
__HALT_COMPILER();
PHP;
$phar->setStub($stub);

// add src files
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $localPath = str_replace($srcDir . DIRECTORY_SEPARATOR, "", $file->getPathname());
        $phar->addFile($file->getPathname(), "src/OmniVersion/" . $localPath);
    }
}

// add plugin.yml from temp (with bumped version)
$phar->addFile($tempPlugin, "plugin.yml");

// add README if exists
if (file_exists(__DIR__ . "/README.md")) {
    $phar->addFile(__DIR__ . "/README.md", "README.md");
}

$phar->stopBuffering();

unlink($tempPlugin);

echo "Build complete: {$pharName}\n";
echo "Phar size: " . filesize($pharName) . " bytes\n";