<?php
namespace MediaWiki\Extension\ThemeToggle\Handlers;

use MediaWiki\Extension\ThemeToggle\ConfigNames;
use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\Hooks\SwitcherHooks;
use MediaWiki\Extension\ThemeToggle\Hooks\ThemeLoadingHooks;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\Extension\ThemeToggle\ThemeToggleConsts;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;
use MediaWiki\ResourceLoader\ResourceLoader;
use Skin;

final class OutputPageHandler implements
    \MediaWiki\Hook\BeforePageDisplayHook,
    \MediaWiki\Hook\OutputPageAfterGetHeadLinksArrayHook
{
    public function __construct(
        private readonly ExtensionConfig $config,
        private readonly ThemeAndFeatureRegistry $registry
    ) { }

    /**
     * Schedules switcher loading, adds body classes, injects logged-in users' theme choices.
     *
     * @param OutputPage $out
     * @param Skin $skin
     */
    public function onBeforePageDisplay( $out, $skin ): void {
        if ( in_array( $skin->getSkinName(), $this->config->get( ConfigNames::DisallowedSkins ) ) ) {
            return;
        }

        if ( !$this->config->get( ConfigNames::EnableForAnonymousUsers ) && !$out->getUser()->isNamed() ) {
            return;
        }

        $this->preloadThemeOntoOutputPage( $out );
        $this->queueSwitcher( $out );
    }

    private function preloadThemeOntoOutputPage( OutputPage $out ): void {
        $currentTheme = $this->registry->getForUser( $out->getUser() );

        // Expose configuration variables
        if ( $out->getUser()->isNamed() && $currentTheme !== 'auto' ) {
            $out->addJsConfigVars( [
                'wgCurrentTheme' => $currentTheme,
            ] );
        }

        $htmlClasses = [];
        $themeId = 'light';
        $themeKind = 'light';

        // Preload the CSS class. For automatic detection, assume light - we can't make a good guess (obviously), but
        // client scripts will correct this.
        if ( $currentTheme !== 'auto' ) {
            $themeId = $currentTheme;
            // getForUser() should have normalised its result, but it's possible the default theme doesn't exist
            $currentThemeInfo = $this->registry->get( $currentTheme );
            if ( $currentThemeInfo ) {
                $themeKind = $currentThemeInfo->getKind();
            }
        } else {
            $htmlClasses[] = 'theme-auto';
            $themeId = 'light';
        }
        $htmlClasses[] = "view-$themeKind";
        $htmlClasses[] = "theme-$themeId";
        $htmlClasses[] = 'skin-theme-clientpref-' . ThemeToggleConsts::KIND_TO_CODEX[$themeKind];
        $out->addHtmlClasses( $htmlClasses );

        // Preload the styles if default or current theme is not bundled with site CSS
        if ( $currentTheme !== 'auto' ) {
            $currentThemeInfo = $this->registry->get( $currentTheme );
            if ( $currentThemeInfo !== null && !$currentThemeInfo->isBundled() ) {
                $out->addLink( [
                    'id' => 'mw-themetoggle-styleref',
                    'rel' => 'stylesheet',
                    'href' => $this->getThemeLoadEndpointUri( $out, [
                        'only' => 'styles',
                        'modules' => "ext.theme.$currentTheme",
                    ] ),
                ] );
            }
        }
    }

    private function queueSwitcher( OutputPage $out ): void {
        if ( in_array(
            $this->config->get( ConfigNames::SwitcherStyle ),
            ThemeToggleConsts::ALLOWED_SWITCHER_STYLE_VALUES
        ) ) {
            $out->addModules( [ 'ext.themes.switcher' ] );
        }
    }

    /**
     * Injects the theme applying script into <head> before meta tags and other extensions' head items. This should
     * help the script get downloaded earlier (ideally it would be scheduled before core JS).
     *
     * @param array &$tags
     * @param OutputPage $out
     * @return void
     */
    public function onOutputPageAfterGetHeadLinksArray( &$tags, $out ) {
        $rlEndpoint = $this->getThemeLoadEndpointUri( $out );
        $skin = $out->getSkin()->getSkinName();
        array_unshift( $tags, Html::element(
            'script',
            [
                'async' => true,
                'src' => "$rlEndpoint&modules=ext.themes.apply&only=scripts&skin=$skin&raw=1",
            ]
        ) );
    }

    private function getThemeLoadEndpointUri( OutputPage $outputPage, array $query = [] ): string {
        return wfAppendQuery( $this->config->getLoadScript(), [
            'lang' => $outputPage->getLanguage()->getCode(),
            'debug' => ResourceLoader::inDebugMode() ? '2' : false,
            'skin' => $outputPage->getSkin()->getSkinName(),
            ...$query,
        ] );
    }
}
