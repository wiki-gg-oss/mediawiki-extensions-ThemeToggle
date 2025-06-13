<?php
namespace MediaWiki\Extension\ThemeToggle\Handlers;

use MediaWiki\Extension\ThemeToggle\ConfigNames;
use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\ResourceLoader\WikiThemeModule;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\ResourceLoader;

final class ResourceLoaderHandler implements
    \MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook
{
    private const SWITCHER_DROPDOWN = 'Dropdown';
    private const SWITCHER_DAYNIGHT = 'DayNight';

    public const ALLOWED_SWITCHER_STYLE_VALUES = [
        'dayNight',
        'simple',
        'dropdown',
        'auto',
    ];

    public function __construct(
        private readonly ExtensionConfig $config,
        private readonly ThemeAndFeatureRegistry $registry
    ) { }

    private function determineSwitcherStyle(): ?string {
        switch ( $this->config->get( ConfigNames::SwitcherStyle ) ) {
            case 'dayNight':
            case 'simple':
                return self::SWITCHER_DAYNIGHT;
            case 'dropdown':
                return self::SWITCHER_DROPDOWN;

            case 'auto':
                // WIKIDEV-279: Use day-night switcher if there's exactly 2 themes and one of them is light, one of them
                // is dark.

                $qualifiesFor2State = count( $this->registry->getIds() ) === 2;
                if ( $qualifiesFor2State ) {
                    $themes = array_values( $this->registry->getAll() );
                    $qualifiesFor2State = $themes[0]->getKind() !== $themes[1]->getKind();
                }

                return $qualifiesFor2State ? self::SWITCHER_DAYNIGHT : self::SWITCHER_DROPDOWN;
        }
        return null;
    }

    private function getSwitcherModuleParams(): ?array {
        switch ( $this->determineSwitcherStyle() ) {
            case self::SWITCHER_DAYNIGHT:
                return [
                    'packageFiles' => [ 'dayNightSwitcher/main.js' ],
                    'styles' => [ "dayNightSwitcher/styles.less" ],
                    'messages' => [
                        'themetoggle-simple-switch',
                        'themetoggle-simple-switch-short',
                    ]
                ];
            case self::SWITCHER_DROPDOWN:
                return [
                    'packageFiles' => [ 'dropdownSwitcher/main.js' ],
                    'styles' => [ "dropdownSwitcher/styles.less" ],
                    'messages' => [
                        'themetoggle-dropdown-switch',
                        'themetoggle-dropdown-section-themes',
                    ]
                ];
        }

        return null;
    }

    public function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ): void {
        $moduleShared = [
            'localBasePath' => 'extensions/ThemeToggle/modules',
            'remoteExtPath' => 'ThemeToggle/modules',
        ];

        // Register additional theme modules. These are loaded from wiki pages and should not have $moduleShared merged.
        foreach ( $this->registry->getAll() as $themeId => $themeInfo ) {
            if ( !$themeInfo->isBundled() ) {
                $resourceLoader->register( 'ext.theme.' . $themeId, [
                    'class' => WikiThemeModule::class,
                    'id' => $themeId,
                ] );
            }
        }

        // Register the theme switcher module
        $switcherParams = $this->getSwitcherModuleParams();
        if ( $switcherParams !== null ) {
            $resourceLoader->register( 'ext.themes.switcher', [
                'class' => RL\FileModule::class,
                'dependencies' => [ 'ext.themes.jsapi' ],
                ...$moduleShared,
                ...$switcherParams,
            ] );
        }
    }
}
