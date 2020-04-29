const path = require('path');
const addCSSEntries = require('./ez.css.config.js');

module.exports = (Encore) => {
    addCSSEntries(Encore);
    Encore.addEntry('toolbar_scripts_js', [

        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.location.change.language.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.content.tree.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.location.view.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.location.tab.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.location.visibility.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.location.update.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.location.tooglecontentpreview.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/button.content.edit.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/udw/move.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/udw/copy.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/udw/swap.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/udw/copy_subtree.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/udw/locations.tab.js'),
        path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/sidebar/extra.actions.js'),
        path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/sidebar/btn/location.edit.js'),
    //    path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/sidebar/btn/user.edit.js'),
    //    path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/sidebar/btn/location.create.js'),
    //    path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/sidebar/instant.filter.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui-assets/Resources/public/vendors/leaflet/dist/leaflet.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.location.load.map.js'),
        path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/sidebar/btn/content.edit.js'),
    //    path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/sidebar/btn/content.hide.js'),
    //    path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/sidebar/btn/content.reveal.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.location.add.custom_url.js'),
    //    path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/button.state.toggle.js'),
        path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.version.edit.conflict.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.location.bookmark.js'),
        //path.resolve('./vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/public/js/scripts/admin.main.translation.update.js'),
    ])
};