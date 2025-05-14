/**
 * Vector 2022 support module.
 *
 * @type {import("../shared").SkinSupportProvider}
 */
module.exports = Object.freeze( {
    ...require( './default.js' ),


    getPortletOwnerElement() {
        return document.querySelector( '#p-vector-user-menu-preferences' );
    },


    getOrCreatePortlet() {
        let retval = document.getElementById( 'pt-themes' );
        if ( !retval ) {
            retval = document.createElement( 'div' );
            retval.id = 'pt-themes';
            retval.className = 'mw-portlet';

            const owner = this.getPortletOwnerElement();
            owner.append( retval );
            owner.classList.remove( 'emptyPortlet' );
        }
        return retval;
    },
} );
