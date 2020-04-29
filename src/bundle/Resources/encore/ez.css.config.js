const path = require('path');

module.exports = (Encore) => {
    Encore.addEntry('eztoolbar-layout-css', [
        path.resolve(__dirname, '../public/scss/eztoolbar.scss'),
        path.resolve('./vendor/ezsystems/ezplatform-admin-ui-assets/Resources/public/vendors/flatpickr/dist/flatpickr.min.css')
    ]);
};
