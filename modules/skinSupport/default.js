/**
 * Fallback skin support module. This was written with Vector 2010 in mind, but should work with any skin of that era.
 *
 * @type {import("../shared").SkinSupportProvider}
 */
module.exports = Object.freeze( {
    getPortletOwnerElement() {
        return document.querySelector( '#p-personal ul' );
    },


    getOrCreatePortlet() {
        let retval = document.getElementById( 'pt-themes' );
        if ( !retval ) {
            retval = document.createElement( 'li' );
            retval.id = 'pt-themes';
            retval.className = 'mw-list-item';
            this.getPortletOwnerElement().prepend( retval );
        }
        return retval;
    },
} );
