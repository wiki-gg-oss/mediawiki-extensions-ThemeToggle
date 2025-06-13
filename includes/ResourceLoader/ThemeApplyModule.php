<?php
namespace MediaWiki\Extension\ThemeToggle\ResourceLoader;

use MediaWiki\Extension\ThemeToggle\ConfigNames;
use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\Hooks\ThemeLoadingHooks;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\Extension\ThemeToggle\ThemeToggleConsts;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\FileModule;
use MediaWiki\ResourceLoader\FilePath;

class ThemeApplyModule extends FileModule {
    protected $targets = [ 'desktop', 'mobile' ];

    public function getScript( Context $context ): array {
		if ( $context->getOnly() !== 'scripts' ) {
			return '/* Requires only=scripts */';
		}

        /** @var ExtensionConfig */
        $config = MediaWikiServices::getInstance()->getService( ExtensionConfig::SERVICE_NAME );
        /** @var ThemeAndFeatureRegistry */
        $registry = MediaWikiServices::getInstance()->getService( ThemeAndFeatureRegistry::SERVICE_NAME );

		$script = $this->stripBom( file_get_contents( __DIR__ . '/../../modules/ext.themes.apply.js' ) );

        $autoTargets = $registry->getAutoDetectionTargets();

        // Perform replacements
        $script = strtr( $script, [
            'VARS.Default' => $context->encodeJson( $registry->getDefaultThemeId() ),
            'VARS.SiteBundledCss' => $context->encodeJson( $registry->getBundledThemeIds() ),
            'VARS.ResourceLoaderEndpoint' => $context->encodeJson( $this->getThemeLoadEndpointUri( $context ) ),
            'VARS.WithPCSSupport' => $autoTargets ? 1 : 0,
            'VARS.WithThemeLoader' => $registry->hasNonBundledThemes() ? 1 : 0,
            'VARS.ThemeKinds' => $context->encodeJson( $registry->getThemeKinds() ),
            'VARS.KindToCodex' => $context->encodeJson( ThemeToggleConsts::KIND_TO_CODEX ),
            'VARS.AutoTarget__Light' => $context->encodeJson( $autoTargets ? $autoTargets[0] : '' ),
            'VARS.AutoTarget__Dark' => $context->encodeJson( $autoTargets ? $autoTargets[1] : '' ),
            'VARS.WithFeatureSupport' => 0,
        ] );
        // Normalise conditions
        $script = strtr( $script, [
            '!1' => '0',
            '!0' => '1'
        ] );
        // Strip @if, @endif sections
        $script = preg_replace( '/\/\* @if \( 0 \) \*\/[\s\S]+?\/\* @endif \*\//m', '', $script );

		return [
			'plainScripts' => [
				[
					'virtualFilePath' => new FilePath(
						'modules/ext.themes.apply.js',
						$this->localBasePath,
						$this->remoteBasePath
					),
					'content' => $script,
				],
			],
		];
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
