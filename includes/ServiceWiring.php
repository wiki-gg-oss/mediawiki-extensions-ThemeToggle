<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\MediaWikiServices;

return [
    ExtensionConfig::SERVICE_NAME => static function (
        MediaWikiServices $services
    ): ExtensionConfig {
        return new ExtensionConfig(
            new ServiceOptions(
                ExtensionConfig::CONSTRUCTOR_OPTIONS,
                $services->getMainConfig()
            )
        );
    },

    ThemeAndFeatureRegistry::SERVICE_NAME => static function (
        MediaWikiServices $services
    ): ThemeAndFeatureRegistry {
        return new ThemeAndFeatureRegistry(
            new ServiceOptions(
                ThemeAndFeatureRegistry::CONSTRUCTOR_OPTIONS,
                $services->getMainConfig()
            ),
            $services->get( ExtensionConfig::SERVICE_NAME ),
            $services->getRevisionLookup(),
            $services->getUserOptionsLookup(),
            $services->getUserGroupManager(),
            $services->getMainWANObjectCache(),
            $services->getObjectCacheFactory()->getLocalServerInstance( CACHE_HASH ),
            $services->getConnectionProvider()
        );
    },
];
