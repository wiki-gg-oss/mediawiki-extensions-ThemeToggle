<?php
namespace MediaWiki\Extension\ThemeToggle\Hooks;

use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;

final class PreferencesHooks implements
    \MediaWiki\Preferences\Hook\GetPreferencesHook,
    \MediaWiki\User\Hook\UserGetDefaultOptionsHook
{
    /** @var ExtensionConfig */
    private ExtensionConfig $config;

    /** @var ThemeAndFeatureRegistry */
    private ThemeAndFeatureRegistry $registry;

    public function __construct(
        ExtensionConfig $config,
        ThemeAndFeatureRegistry $registry
    ) {
        $this->config = $config;
        $this->registry = $registry;
    }

    public function onUserGetDefaultOptions( &$defaultOptions ) {
        $defaultOptions[$this->config->getThemePreferenceName()] = $this->registry->getDefaultThemeId();
    }

    public function onGetPreferences( $user, &$preferences ) {
        global $wgHiddenPrefs;

        $themeOptions = [];

        if ( $this->registry->isEligibleForAuto() ) {
            $themeOptions['theme-auto-preference-description'] = 'auto';
        }

        foreach ( $this->registry->getAvailableForUser( $user ) as $themeId => $themeInfo ) {
            $themeOptions[$themeInfo->getMessageId()] = $themeId;
        }

        $preferences[$this->config->getThemePreferenceName()] = [
            'label-message' => 'themetoggle-prefs-theme-label',
            'help-message' => 'themetoggle-prefs-theme-help',
            'type' => 'select',
            'options-messages' => $themeOptions,
            'section' => 'rendering/skin/skin-prefs',
        ];

        // The theme preference should only be shown when there's at least two themes to choose from. Hide it, but don't
        // remove it, so MediaWiki stays aware of it.
        if ( count( $themeOptions ) < 2 ) {
            $wgHiddenPrefs[] = $this->config->getThemePreferenceName();
        }
    }
}
