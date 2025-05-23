<?php
namespace MediaWiki\Extension\ThemeToggle\Hooks;

use MediaWiki\Extension\ThemeToggle\ConfigNames;
use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\ResourceLoader\WikiThemeModule;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\Output\OutputPage;
use MediaWiki\ResourceLoader\ResourceLoader;
use Skin;

final class ThemeLoadingHooks implements
    \MediaWiki\Hook\BeforePageDisplayHook,
    \MediaWiki\Hook\OutputPageAfterGetHeadLinksArrayHook,
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

        $isAnonymous = $out->getUser()->isAnon();
        if ( !$this->config->get( ConfigNames::EnableForAnonymousUsers ) && $isAnonymous ) {
            return;
        }

        $currentTheme = $this->registry->getForUser( $out->getUser() );

        // Expose configuration variables
        if ( !$isAnonymous && $currentTheme !== 'auto' ) {
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
        $htmlClasses[] = 'skin-theme-clientpref-' . self::KIND_TO_CODEX[$themeKind];
        $out->addHtmlClasses( $htmlClasses );

        // Preload the styles if default or current theme is not bundled with site CSS
        if ( $currentTheme !== 'auto' ) {
            $currentThemeInfo = $this->registry->get( $currentTheme );
            if ( $currentThemeInfo !== null && !$currentThemeInfo->isBundled() ) {
                $out->addLink( [
                    'id' => 'mw-themetoggle-styleref',
                    'rel' => 'stylesheet',
                    'href' => wfAppendQuery( $this->getThemeLoadEndpointUri( $out ), [
                        'only' => 'styles',
                        'modules' => "ext.theme.$currentTheme",
                    ] ),
                ] );
            }
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
        $html = $this->makeScriptTag(
            $out,
            '',
            "async src=\"$rlEndpoint&modules=ext.themes.apply&only=scripts&skin=$skin&raw=1\""
        );
        array_unshift( $tags, $html );
    }

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

    private function makeScriptTag( OutputPage $outputPage, string $script, $attributes = false ) {
        $nonce = $outputPage->getCSP()->getNonce();
        return sprintf(
            '<script%s%s>%s</script>',
            $nonce !== false ? " nonce=\"$nonce\"" : '',
            $attributes !== false ? " $attributes" : '',
            $script
        );
    }

    private function injectScriptTag( OutputPage $outputPage, string $id, string $script, $attributes = false ) {
        $outputPage->addHeadItem( $id, $this->makeScriptTag( $outputPage, $script, $attributes ) );
    }

    private function getThemeLoadEndpointUri( OutputPage $outputPage ): string {
        return wfAppendQuery( $this->config->getLoadScript(), [
            'lang' => $outputPage->getLanguage()->getCode(),
            'debug' => ResourceLoader::inDebugMode() ? '2' : false,
            'skin' => $outputPage->getSkin()->getSkinName(),
        ] );
    }
}
