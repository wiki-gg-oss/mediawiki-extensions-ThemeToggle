/**
 * @typedef {Object} SkinSupportProvider
 * @property {() => HTMLElement?} getPortletOwnerElement Returns an element to add the portlet to.
 * @property {() => HTMLElement?} getOrCreatePortlet Returns (and creates if needed) a portlet to mount ThemeToggle in.
 */

/**
 * @typedef {Object} SwitcherConfig
 * @property {string?} preferenceGroup
 * @property {boolean} supportsAuto
 * @property {string[]} themes
 * @property {string} defaultTheme
 * @property {string[]} features
 * @property {string} skinSupportScript
 */

/** @type {SwitcherConfig} */
module.exports.CONFIG = require( './config.json' );
/** @type {string} */
module.exports.LOCAL_PREF_NAME = 'skin-theme';
/** @type {string} */
module.exports.LOCAL_FEATURES_PREF_NAME = 'skin-theme-features';
/** @type {string} */
module.exports.REMOTE_PREF_NAME = 'skinTheme-' + ( module.exports.CONFIG.preferenceGroup || mw.config.get( 'wgWikiID' ) );

/** @type {SkinSupportProvider} */
const skinSupport = require( `./skinSupport/${module.exports.CONFIG.skinSupportScript}.js` );

/** @type {BroadcastChannel|false} */
const themeChangeChannel = window.BroadcastChannel && new BroadcastChannel( 'ThemeToggle__OnThemeChange' );

/** @type {bool} */
const isAnonymous = mw.config.get( 'wgUserName' ) === null;


function _setAccountPreference( value ) {
    mw.loader.using( 'mediawiki.api' ).then( function () {
        var api = new mw.Api();
        api.post( {
            action: 'options',
            format: 'json',
            optionname: module.exports.REMOTE_PREF_NAME,
            optionvalue: value,
            token: mw.user.tokens.get( 'csrfToken' )
        } );
    } );
}


module.exports.getAvailableThemes = function () {
    const userGroups = mw.config.get( 'wgUserGroups' );
    return this.CONFIG.themes
        .map( item => {
            if ( item.userGroups ) {
                return item.userGroups.some( entitled => userGroups.includes( entitled ) ) ? item.id : null;
            }
            return item.id;
        } )
        .filter( Boolean );
}


module.exports.getAvailableThemesEx = function () {
    const userGroups = mw.config.get( 'wgUserGroups' );
    return this.CONFIG.themes
        .map( item => {
            if ( item.userGroups ) {
                return item.userGroups.some( entitled => userGroups.includes( entitled ) ) ? item : null;
            }
            return item;
        } )
        .filter( Boolean );
}


module.exports.getSwitcherPortlet = function () {
    return skinSupport.getPortletOwnerElement();
};


module.exports.getSwitcherMountPoint = function () {
    return skinSupport.getOrCreatePortlet();
};


/**
 * Checks whether local preference points to a valid theme, and if not, erases it and requests the default theme to be
 * set.
 */
module.exports.trySanitisePreference = function () {
    if ( isAnonymous && !this.CONFIG.themes.includes( localStorage.getItem( module.exports.LOCAL_PREF_NAME ) ) ) {
        module.exports.changeTheme( module.exports.CONFIG.defaultTheme, {
            fireHooks: false,
            remember: true,
        } );
    }
};


module.exports.trySyncNewAccount = function () {
    if ( !isAnonymous ) {
        var prefValue = localStorage.getItem( module.exports.LOCAL_PREF_NAME );
        if ( prefValue ) {
            localStorage.removeItem( module.exports.LOCAL_PREF_NAME );
            _setAccountPreference( prefValue );
        }
    }
};


/**
 * Changes current theme. This function allows more fine-grained control than setUserPreference.
 *
 * @param {string} value Target theme identifier.
 * @param {boolean} [fireHooks=true] Whether hooks should be fired.
 * @param {boolean} [remember=false] Whether to remember the change.
 * @param {boolean} [broadcast=false] Whether to propagate the change to other tabs.
 */
module.exports.changeTheme = function (
    value,
    {
        fireHooks = true,
        remember = false,
        broadcast = false
    }
) {
    if ( remember ) {
        if ( !isAnonymous ) {
            // Registered user: save the theme server-side
            _setAccountPreference( value );
        } else {
            // Anonymous user: save the theme in their browser's local storage
            if ( value === module.exports.CONFIG.defaultTheme ) {
                localStorage.removeItem( module.exports.LOCAL_PREF_NAME );
            } else {
                localStorage.setItem( module.exports.LOCAL_PREF_NAME, value );
            }
        }
    }

    MwSkinTheme.set( value );
    const current = MwSkinTheme.getCurrent();

    if ( fireHooks ) {
        mw.hook( 'ext.themes.themeChanged' ).fire( current );
    }

    // Inform other tabs of the change
    if ( broadcast && themeChangeChannel ) {
        themeChangeChannel.postMessage( current );
    }
};


/**
 * Changes a feature's enablement state. This function allows more fine-grained control than setUserPreference.
 *
 * @param {string} featureId Target feature identifier.
 * @param {boolean} value New enablement status. 'True' enables.
 * @param {boolean} [fireHooks=true] Whether hooks should be fired.
 * @param {boolean} [remember=false] Whether to remember the change.
 * @param {boolean} [broadcast=false] Whether to propagate the change to other tabs.
 */
module.exports.changeFeatureState = function (
    featureId,
    value,
    {
        fireHooks = true,
        remember = false,
        broadcast = false
    }
) {
    if ( remember ) {
        // TODO: implement
    }

    MwSkinTheme.setFeature( featureId, value );

    if ( fireHooks ) {
        // TODO: implement
    }

    if ( broadcast ) {
        // TODO: implement
    }
};


/**
 * Changes current theme, remembers it, and broadcasts the change. Switchers should invoke this function when the theme
 * is set because of a user interaction.
 *
 * @param {string} value Target theme identifier.
 */
module.exports.setUserPreference = function ( value ) {
    this.changeTheme( value, { fireHooks: true, remember: true, broadcast: true } );
};


/**
 * Changes feature state, remembers it, and broadcasts the change. Switchers should invoke this function when the
 * feature is set because of a user interaction.
 *
 * @param {string} featureId Target feature identifier.
 * @param {boolean} value New enablement status. 'True' enables.
 */
module.exports.setFeatureUserPreference = function ( featureId, value ) {
    this.changeFeatureState( value, { fireHooks: true, remember: true, broadcast: true } );
};


module.exports.whenCoreLoaded = function ( callback, context ) {
    // TODO: switch to __MwExtTt
    if ( 'MwSkinTheme' in window ) {
        callback.apply( context );
    } else {
        setTimeout( module.exports.whenCoreLoaded.bind( null, callback, context ), 20 );
    }
};


module.exports.prepare = function () {
    module.exports.trySanitisePreference();
    module.exports.trySyncNewAccount();

    // Listen to theme changes in other tabs
    if ( themeChangeChannel ) {
        themeChangeChannel.onmessage = themeName => {
            // Process this broadcast only if we know the theme and aren't using it already
            if ( themeName && this.CONFIG.themes.includes( themeName ) && themeName !== MwSkinTheme.getCurrent() ) {
                this.changeTheme( themeName );
            }
        };
    }
};


module.exports.runSwitcherInitialiser = function ( fn ) {
    if ( module.exports.CONFIG.themes.length > 1 ) {
        module.exports.whenCoreLoaded( () => $( () => {
            fn();
            mw.hook( 'ext.themes.switcherReady' ).fire();
        } ) );
    }
};


// Broadcast the `ext.themes.themeChanged( string )` hook when core is loaded
module.exports.whenCoreLoaded( function () {
    mw.hook( 'ext.themes.themeChanged' ).fire( MwSkinTheme.getCurrent() );
} );
