/* eslint-disable mediawiki/class-doc */

/**
 * This script implements the `ext.themes.apply` module.
 *
 * Previously, this had been actually two scripts: one serving the case with prefers-color-scheme support, and one
 * without. However, a lot of the code was either similar or identical, and the maintenance cost added up requiring a
 * lot of extra testing and carefulness, which would only worsen as non-theme feature handling is implemented.
 *
 * This is the main entrypoint starting with v0.6.0, with some really nasty "dead" code elimination handled in PHP
 * (ThemeApplyModule class). Conditional code should be wrapped with an @if comment along with @endif at the end of the
 * block, with only a single level of depth supported. Cried when implementing that.
 */


( function () {
    var
        /* @if ( VARS.WithThemeLoader ) */
        LINK_ID = 'mw-themetoggle-styleref',
        linkNode = document.getElementById( LINK_ID ),
        /* @endif */
        /* @if ( VARS.WithPCSSupport ) */
        prefersDark = window.matchMedia( '(prefers-color-scheme: dark)' ),
        /* @endif */
        htmlNode = document.documentElement,
        currentTheme = null;


    function _setThemeImpl( actualTarget ) {
        try {
            // Apply by changing class
            if ( actualTarget !== null ) {
                // Remove all theme classes
                htmlNode.className = htmlNode.className.replace( / (theme|view)-[^\s]+/ig, '' );
                // Add new theme class
                const themeKind = VARS.ThemeKinds[ actualTarget ] || 'unknown';
                htmlNode.classList.add( 'theme-' + actualTarget, `view-${themeKind}` );
            }

            /* @if ( VARS.WithThemeLoader ) */
            if ( VARS.SiteBundledCss.indexOf( actualTarget ) < 0 ) {
                if ( linkNode === null ) {
                    linkNode = document.createElement( 'link' );
                    document.head.appendChild( linkNode );
                }
                linkNode.id = LINK_ID;
                linkNode.rel = 'stylesheet';
                linkNode.type = 'text/css';
                linkNode.href = VARS.ResourceLoaderEndpoint + '&modules=ext.theme.' + actualTarget;
            } else if ( linkNode !== null ) {
                document.head.removeChild( linkNode );
                linkNode = null;
            }
            /* @endif */
        } catch ( ex ) {
            setTimeout( function () {
                throw ex;
            }, 0 );
        }
    }


    /* @if ( VARS.WithPCSSupport ) */
    function _setFromPCS() {
        _setThemeImpl( prefersDark.matches ? 'dark' : 'light' );
    }
    /* @endif */


    window.MwSkinTheme = Object.freeze( {
        LOCAL_THEME_PREFERENCE_KEY: 'skin-theme',
        LOCAL_FEATURE_PREFERENCE_KEY: 'skin-theme-features',


        getCurrent: function () {
            return currentTheme;
        },


        set: function ( target ) {
            currentTheme = target;

            /* @if ( VARS.WithPCSSupport ) */
            if ( currentTheme === 'auto' ) {
                // Detect preferred theme by prefers-color-scheme
                _setFromPCS();
                htmlNode.classList.add( 'theme-auto' );
                // Attach listener for future changes
                prefersDark.addEventListener( 'change', _setFromPCS );
            } else {
                // Apply the theme choice and stop tracking prefers-color-scheme changes
                _setThemeImpl( currentTheme );
                prefersDark.removeEventListener( 'change', _setFromPCS );
            }
            /* @endif */

            /* @if ( !VARS.WithPCSSupport ) */
            _setThemeImpl( target );
            /* @endif */
        },


        /* @if ( VARS.WithFeatureSupport ) */
        toggleFeature: function ( id, value ) {
            htmlNode.classList[ value ? 'add' : 'remove' ]( 'theme-feature-' + id );
        }
        /* @endif */
    } );


    MwSkinTheme.set( localStorage.getItem( MwSkinTheme.LOCAL_THEME_PREFERENCE_KEY ) || RLCONF.wgCurrentTheme || VARS.Default );
    /* @if ( VARS.WithFeatureSupport ) */
    JSON.parse( localStorage.getItem( MwSkinTheme.LOCAL_FEATURE_PREFERENCE_KEY ) || '[]' ).forEach( function ( id ) {
        MwSkinTheme.toggleFeature( id, true );
    } );
    /* @endif */
}() );
