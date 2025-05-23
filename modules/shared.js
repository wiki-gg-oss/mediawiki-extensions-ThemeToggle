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
    var userGroups = mw.config.get( 'wgUserGroups' );
    return this.CONFIG.themes
        .map( function ( item ) {
            if ( item.userGroups ) {
                return item.userGroups.some( function ( entitled ) {
                    return userGroups.indexOf( entitled ) >= 0;
                } ) ? item.id : null;
            }
            return item;
        } )
        .filter( Boolean );
};


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
        module.exports.setUserPreference( module.exports.CONFIG.defaultTheme, false );
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


module.exports.setUserPreference = function ( value, fireHooks = true ) {
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

    MwSkinTheme.set( value );

    if ( fireHooks ) {
        mw.hook( 'ext.themes.themeChanged' ).fire( MwSkinTheme.getCurrent() );
    }
};


module.exports.toggleFeature = function ( id ) {
    if ( module.exports.CONFIG.features.indexOf( id ) < 0 ) {
        return;
    }

    var features = JSON.parse( localStorage.getItem( module.exports.LOCAL_FEATURES_PREF_NAME ) || '[]' ),
        arrayIndex = features.indexOf( id );
    if ( arrayIndex < 0 ) {
        features.push( id );
    } else {
        delete features[ arrayIndex ];
    }
    localStorage.setItem( module.exports.LOCAL_FEATURES_PREF_NAME, features );
    MwSkinTheme.toggleFeature( id, arrayIndex < 0 );
};


module.exports.whenCoreLoaded = function ( callback, context ) {
    if ( 'MwSkinTheme' in window ) {
        callback.apply( context );
    } else {
        setTimeout( module.exports.whenCoreLoaded.bind( null, callback, context ), 20 );
    }
};


module.exports.prepare = function () {
    module.exports.trySanitisePreference();
    module.exports.trySyncNewAccount();
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
