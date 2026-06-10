<?php
/**
 * Сборка ZIP для загрузки через Плагины → Добавить → Загрузить.
 * Запуск из корня плагина: php build-zip.php
 */

$pluginDir = __DIR__;
$pluginSlug = 'yoga-tariff-renewal';
$outputZip = dirname($pluginDir, 3) . DIRECTORY_SEPARATOR . $pluginSlug . '.zip';

if (!class_exists('ZipArchive')) {
	fwrite(STDERR, "ZipArchive extension required.\n");
	exit(1);
}

$zip = new ZipArchive();
if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
	fwrite(STDERR, "Cannot create {$outputZip}\n");
	exit(1);
}

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($pluginDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
	if (!$fileInfo->isFile()) {
		continue;
	}

	$path = $fileInfo->getPathname();
	$basename = $fileInfo->getBasename();

	if ($basename === 'build-zip.php' || $basename === '.gitkeep') {
		continue;
	}

	$relative = substr($path, strlen($pluginDir) + 1);
	$entry = $pluginSlug . '/' . str_replace('\\', '/', $relative);
	$zip->addFile($path, $entry);
}

$zip->close();

echo "Created: {$outputZip}\n";
echo 'Size: ' . filesize($outputZip) . " bytes\n";

$check = new ZipArchive();
$check->open($outputZip);
for ($i = 0; $i < min(5, $check->numFiles); $i++) {
	echo $check->getNameIndex($i) . "\n";
}
$check->close();
