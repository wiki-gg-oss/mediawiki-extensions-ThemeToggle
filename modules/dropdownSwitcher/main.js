/*
 * Dropdown-based theme toggle
*/

var Shared = require( 'ext.themes.jsapi' );
var $container, $themeHeading, $list;


function addTheme( themeId ) {
    var $link = document.createElement( 'a' );
    $link.href = '#';
    // eslint-disable-next-line mediawiki/msg-doc
    $link.innerText = mw.msg( 'theme-' + themeId );

    var $item = document.createElement( 'li' );
    $item.className = 'mw-list-item';
    $item.id = 'pt-themes-item-' + themeId;
    $item.appendChild( $link );
    $item.addEventListener( 'click', function ( event ) {
        event.preventDefault();
        Shared.setUserPreference( themeId );
        // eslint-disable-next-line mediawiki/msg-doc
        $themeHeading.innerText = mw.msg( 'theme-' + themeId );
    } );

    $list.appendChild( $item );
}


function initialise() {
    Shared.prepare();

    var $metaHeading = document.createElement( 'span' );
    $metaHeading.innerText = mw.msg( 'themetoggle-skinprefs' );

    $themeHeading = document.createElement( 'span' );
    // eslint-disable-next-line mediawiki/msg-doc
    $themeHeading.innerText = mw.msg( 'theme-' + MwSkinTheme.getCurrent() );

    var $label = document.createElement( 'label' );
    $label.id = 'pt-themes-label';
    $label.htmlFor = 'pt-themes-checkbox';
    $label.appendChild( $metaHeading );
    $label.appendChild( $themeHeading );

    var $toggle = document.createElement( 'input' );
    $toggle.id = $label.htmlFor;
    $toggle.type = 'checkbox';
    $toggle.setAttribute( 'role', 'button' );
    $toggle.setAttribute( 'aria-haspopup', 'true' );
    $toggle.setAttribute( 'aria-labelledby', $label.htmlFor );
    $toggle.title = mw.msg( 'themetoggle-dropdown-switch' );

    var $themeSection = document.createElement( 'li' );
    $themeSection.innerText = mw.msg( 'themetoggle-dropdown-section-themes' );

    $list = document.createElement( 'ul' );
    $list.appendChild( $themeSection );

    var $popup = document.createElement( 'div' );
    $popup.className = 'ext-themetoggle-popup';
    $popup.appendChild( $list );

    $container = Shared.getSwitcherMountPoint();
    $container.appendChild( $toggle );
    $container.appendChild( $label );
    $container.appendChild( $popup );

    if ( Shared.CONFIG.supportsAuto ) {
        addTheme( 'auto' );
    }
    Shared.getAvailableThemes().forEach( addTheme );

    Shared.getSwitcherPortlet().prepend( $container );

    mw.hook( 'ext.themes.dropdownSwitcherReady' ).fire( $container );
}


Shared.runSwitcherInitialiser( initialise );
