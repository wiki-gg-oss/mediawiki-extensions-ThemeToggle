<?php
namespace MediaWiki\Extension\ThemeToggle\Hooks;

use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\ResourceLoader\WikiThemeModule;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\ResourceLoader\ResourceLoader;

final class ThemeLoadingHooks implements
    \MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook
{
    public const KIND_TO_CODEX = [
        'dark' => 'night',
        'light' => 'day',
        'unknown' => 'day',
    ];

    public function __construct(
        private readonly ExtensionConfig $config,
        private readonly ThemeAndFeatureRegistry $registry
    ) { }

    public function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ): void {
        foreach ( $this->registry->getAll() as $themeId => $themeInfo ) {
            if ( !$themeInfo->isBundled() ) {
                $resourceLoader->register( 'ext.theme.' . $themeId, [
                    'class' => WikiThemeModule::class,
                    'id' => $themeId
                ] );
            }
        }
    }
}
