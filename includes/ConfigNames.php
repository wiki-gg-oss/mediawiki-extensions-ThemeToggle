<?php
namespace MediaWiki\Extension\ThemeToggle;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

/**
 * A class containing constants representing the names of ThemeToggle' configuration variables. These constants can be
 * used with ServiceOptions or via ExtensionConfig to protect against typos.
 *
 * @since 1.0.0
 */
class ConfigNames {
    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const DefaultTheme = 'ThemeToggleDefault';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const DisableAutoDetection = 'ThemeToggleDisableAutoDetection';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const SwitcherStyle = 'ThemeToggleSwitcherStyle';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const EnableForAnonymousUsers = 'ThemeToggleEnableForAnonymousUsers';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const PreferenceSuffix = 'ThemeTogglePreferenceGroup';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const LoadScriptOverride = 'ThemeToggleLoadScriptOverride';
}
