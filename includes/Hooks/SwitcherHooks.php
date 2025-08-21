<?php
namespace MediaWiki\Extension\ThemeToggle\Hooks;

use MediaWiki\Extension\ThemeToggle\ConfigNames;
use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\ResourceLoader\SharedJsModule;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\MainConfigNames;
use MediaWiki\Output\OutputPage;
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\ResourceLoader;
use Skin;

final class SwitcherHooks implements
    \MediaWiki\Hook\BeforePageDisplayHook,
    \MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook
{
    private const SWITCHER_DROPDOWN = 'Dropdown';
    private const SWITCHER_DAYNIGHT = 'DayNight';

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

    /**
     * Schedules switcher loading, adds body classes, injects logged-in users' theme choices.
     *
     * @param OutputPage $out
     * @param Skin $skin
     */
    public function onBeforePageDisplay( $out, $skin ): void {
        $isAnonymous = $out->getUser()->isAnon();
        if ( !$this->config->get( ConfigNames::EnableForAnonymousUsers ) && $isAnonymous ) {
            return;
        }

        // Check if site CSS is allowed; server-side-applied classes without any scripts should be sufficient in case
        // this is a high-risk special
        $allowsSiteCss = $this->config->get( MainConfigNames::AllowSiteCSSOnRestrictedPages )
            || $out->getAllowedModules( RL\Module::TYPE_STYLES ) >= RL\Module::ORIGIN_USER_SITEWIDE;
        if ( !$allowsSiteCss ) {
            return;
        }

        // Inject the theme switcher as a ResourceLoader module
        if ( $this->getSwitcherStyle() !== null ) {
            $out->addModules( [ 'ext.themes.switcher' ] );
        }
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
