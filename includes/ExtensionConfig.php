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
        ConfigNames::DisallowedSkins,
        ConfigNames::FeatureVar_EnableFeatures,
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

    public function getThemePreferenceName(): string {
        $suffix = $this->options->get( ConfigNames::PreferenceSuffix );
        if ( $suffix === false ) {
            return 'skinTheme';
        }
        return "skinTheme-$suffix";
    }
}
