# Theme Toggle extension

This is free software licensed under the GNU General Public License. Please
see http://www.gnu.org/copyleft/gpl.html for further details, including the
full text and terms of the license.

## Overview
Offers theming support with a low flash of wrongly themed content for wikis behind aggressive caching.

[Test installation link (may be broken)](https://1.37.wiki-dev.mglolenstine.xyz/wiki/ARK_Survival_Evolved_Wiki).

## Installation
Manual installation:
1. Clone the repository to `extensions/ThemeToggle`.
2. Check out the release branch for your MediaWiki version.
3. `wfLoadExtension` in site configuration.
4. Consider deploying the extension only to registered users for the configuration period. Set `$wgThemeToggleEnableForAnonymousUsers` to `false`.
5. If deploying to ALL visitors, purge the cache for HTML pages.

## Configuration
* `$wgThemeToggleEnableForAnonymousUsers`: whether the extension is active for anonymous users.
* * **Changing this requires purging cache.**
* * Defaults to `true`.
* `$wgThemeToggleDefault`: theme that will be used by default for anonymous and new users.
* * **Changing this requires purging cache.**
* * Defaults to `auto`.
* `$wgThemeTogglePreferenceGroup`: suffix to add to the preference name on this wiki. Set this on wiki-farms if user preferences are shared and you want a wiki to have a separate theme toggle.
* * Defaults to wiki ID.
* * **Deprecated:** This variable will be removed in a future release. Setting to `false` will enable new behaviour (theme preference key will always be `skinTheme`).
* `$wgThemeToggleDisableAutoDetection`: cuts away support for the `auto`matic theme detection (based on prefers-color-scheme). Only set this if you are sure you don't need automatic detection, or do not use a `light` and `dark` theme combination.
* * Defaults to `false`.
* `$wgThemeToggleSwitcherStyle`: switcher style.
* * Defaults to `auto`.
* * Possible values:
* * * `simple`: an icon-based switcher that cycles through themes.
* * * `dropdown`: a dropdown-based switcher.
* * * `auto`: simple if one or two themes, dropdown if more.
* * * `none`: no switcher.
* * No cache purge required.
* `$wgThemeToggleLoadScriptOverride`: controls the ResourceLoader endpoint used for loading themes.
* * Defaults to `$wgLoadScript`.
* * Set to another wiki's `load.php` to load theme modules from it.
* * `MediaWiki:Theme-definitions` and configuration settings still need to be updated manually.