const path = require('path');

module.exports = (Encore) => {
    Encore.addEntry('eztoolbar-layout-css', [
        path.resolve(__dirname, '../public/scss/eztoolbar.scss'),
    ]);
};
