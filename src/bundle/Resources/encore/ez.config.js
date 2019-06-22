const path = require('path');
const addCSSEntries = require('./ez.css.config.js');

module.exports = (Encore) => {
    addCSSEntries(Encore);
};
