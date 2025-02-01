<?php
namespace MediaWiki\Extension\ThemeToggle;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\MainConfigNames;
use MediaWiki\WikiMap\WikiMap;

class ExtensionConfig {
    public const SERVICE_NAME = 'ThemeToggle.Config';

    /**
     * @internal Use only in ServiceWiring
     */
    public const CONSTRUCTOR_OPTIONS = [
        ConfigNames::DefaultTheme,
        ConfigNames::DisableAutoDetection,
        ConfigNames::SwitcherStyle,
        ConfigNames::EnableForAnonymousUsers,
        ConfigNames::PreferenceSuffix,
        ConfigNames::LoadScriptOverride,
        // MW variables
        MainConfigNames::LoadScript,
    ];

    /** @var ServiceOptions */
    private ServiceOptions $options;

    public function __construct( ServiceOptions $options ) {
        $options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
        $this->options = $options;
    }

    public function get( string $key ) {
        return $this->options->get( $key );
    }

    public function getLoadScript(): string {
        return $this->options->get( ConfigNames::LoadScriptOverride )
            ?? $this->options->get( MainConfigNames::LoadScript );
    }

    public function getPreferenceSuffix(): string {
        return $this->options->get( ConfigNames::PreferenceSuffix ) ?? WikiMap::getCurrentWikiId();
    }

    public function getThemePreferenceName(): string {
        return 'skinTheme-' . $this->getPreferenceSuffix();
    }
}
