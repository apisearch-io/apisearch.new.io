<?php

use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require "vendor/autoload.php";

$twig = new Environment(new FilesystemLoader([
    __DIR__ . '/templates'
]));

$config = Yaml::parse(file_get_contents(__DIR__ . '/config.yml'));
$config['assets'] = trim($config['assets'] ?? 'assets', '/');
$config['target'] = trim($config['target'] ?? 'public', '/');
$config['languages'] = $config['languages'] ?? 'en';
$config['files'] = $config['files'] ?? [];


$target = trim($config['target'], '/');
$languages = $config['languages'];
$pages = $config['pages'];

exec("rm -Rf " . __DIR__ . "/$target");
mkdir(__DIR__ . "/$target");

foreach ($languages as $language) {
    $languageTranslations = Yaml::parse(file_get_contents(__DIR__ . "/languages/$language.yml"));
    if ($language !== $config['root_language']) {
        mkdir(__DIR__ . "/$target/$language");
    }
    foreach ($pages as $page) {
        generatePageInLanguage(
            $twig,
            $page,
            $language,
            array_merge(
                $languageTranslations[$page] ?? [],
                $languageTranslations['global'] ?? []
            ),
            $config
        );
    }
}

copyResources($config);

/**
 * @param Environment $twig
 * @param string      $page
 * @param string      $language
 * @param array       $translations
 * @param array       $config
 */
function generatePageInLanguage(
    Environment $twig,
    string $page,
    string $language,
    array $translations,
    array $config
)
{
    $target = $config['target'];
    $assets = $config['assets'];
    $isRootLanguage = $language === $config['root_language'];
    $rootPath = $isRootLanguage ? '.' : '..';
    $languagePath = $isRootLanguage
        ? ''
        : "/$language";

    $content = $twig->render("$page.twig", [
        'config' => $config,
        't' => $translations,
        'root_path' => $rootPath,
        'assets_path' => $rootPath . '/' . $assets,
    ]);

    file_put_contents(__DIR__ . "/{$target}{$languagePath}/$page", $content);
}

/**
 * @param array $config
 */
function copyResources(array $config)
{
    $target = $config['target'];
    $assets = $config['assets'];
    $sourcePath = __DIR__ . "/$assets";
    $targetPath = __DIR__ . "/$target/$assets";
    exec("cp -R $sourcePath $targetPath");

    foreach ($config['files'] as $file) {
        $sourcePath = __DIR__ . "/$file";
        $targetPath = __DIR__ . "/$target/$file";
        exec("cp $sourcePath $targetPath");
    }
}
