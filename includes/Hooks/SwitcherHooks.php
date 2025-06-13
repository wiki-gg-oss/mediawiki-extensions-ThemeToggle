<?php
namespace MediaWiki\Extension\ThemeToggle\Hooks;

use MediaWiki\Extension\ThemeToggle\ConfigNames;
use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\ResourceLoader;

final class SwitcherHooks implements
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

    private function getSwitcherStyle(): ?string {
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

    public function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ): void {
        $moduleShared = [
            'localBasePath' => 'extensions/ThemeToggle/modules',
            'remoteExtPath' => 'ThemeToggle/modules',
        ];

        // Register the switcher
        $style = $this->getSwitcherStyle();
        if ( $style !== null ) {
            $resourceLoader->register( 'ext.themes.switcher', [
                'class' => RL\FileModule::class,
                'dependencies' => [ 'ext.themes.jsapi' ],
                ...$moduleShared,
            ] + $this->getModuleDefinitionForStyle( $style ) );
        }
    }

    private function getModuleDefinitionForStyle( string $style ): array {
        switch ( $style ) {
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
    }
}
