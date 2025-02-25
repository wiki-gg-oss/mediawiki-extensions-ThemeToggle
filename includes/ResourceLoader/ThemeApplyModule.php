<?php
namespace MediaWiki\Extension\ThemeToggle\ResourceLoader;

use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\Hooks\ThemeLoadingHooks;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\FileModule;

class ThemeApplyModule extends FileModule {
    protected $targets = [ 'desktop', 'mobile' ];

    public function getScript( Context $context ): string {
        $script = parent::getScript( $context );

        /** @var ExtensionConfig */
        $config = MediaWikiServices::getInstance()->getService( ExtensionConfig::SERVICE_NAME );
        /** @var ThemeAndFeatureRegistry */
        $registry = MediaWikiServices::getInstance()->getService( ThemeAndFeatureRegistry::SERVICE_NAME );

        $currentTheme = $registry->getDefaultThemeId();

        // Unwrap the script contents if we received an array from FileModule
        if ( is_array( $script ) ) {
            $script = $script['plainScripts']['inline.js']['content'];
        }

        // Perform replacements
        $script = strtr( $script, [
            'VARS.Default' => $context->encodeJson( $currentTheme ),
            'VARS.SiteBundledCss' => $context->encodeJson( $registry->getBundledThemeIds() ),
            'VARS.ResourceLoaderEndpoint' => $context->encodeJson( $this->getThemeLoadEndpointUri( $context ) ),
            'VARS.WithPCSSupport' =>
                !$config->get( ConfigNames::DisableAutoDetection ) && $registry->isEligibleForAuto() ? 1 : 0,
            'VARS.WithThemeLoader' => $registry->hasNonBundledThemes() ? 1 : 0,
            'VARS.ThemeKinds' => $context->encodeJson( $registry->getThemeKinds() ),
            'VARS.KindToCodex' => $context->encodeJson( ThemeLoadingHooks::KIND_TO_CODEX ),
            'VARS.WithFeatureSupport' => 0,
        ] );
        $script = strtr( $script, [
            // Normalise conditions
            '!1' => '0',
            '!0' => '1'
        ] );
        $script = preg_replace( '/\/\* @if \( 0 \) \*\/[\s\S]+?\/\* @endif \*\//m', '', $script );

        return $script;
    }

    private function getThemeLoadEndpointUri( Context $context ): string {
        $loadScript = MediaWikiServices::getInstance()->getService( ExtensionConfig::SERVICE_NAME )->getLoadScript();
        $language = $context->getLanguage();
        $skin = $context->getSkin();

        $out = "$loadScript?lang=$language&only=styles&skin=$skin";
        if ( $context->getDebug() ) {
            $out .= '&debug=1';
        }

        return $out;
    }

    public function supportsURLLoading(): bool {
        return false;
    }

    public function enableModuleContentVersion(): bool {
        // Enabling this means that ResourceLoader::getVersionHash will simply call getScript()
        // and hash it to determine the version (as used by E-Tag HTTP response header).
        return true;
    }
}
