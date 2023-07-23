const path = require('path');

module.exports = (Encore) => {
    Encore.addEntry('ezplatform-automated-translation-js', [
        path.resolve('./public/bundles/ibexaadminui/js/scripts/admin.content.edit.js'),
        path.resolve('./public/bundles/ibexaadminui/js/scripts/fieldType/base/base-field.js'),
        path.resolve(__dirname, '../public/admin/js/validator/auto-translation-ezselection.js'),
        path.resolve(__dirname, '../public/admin/js/validator/auto-translation-ezobjectrelationlist.js'),
        path.resolve(__dirname, '../public/admin/js/ezplatformautomatedtranslation.js'),
        path.resolve(__dirname, '../public/admin/js/auto-translation.js'),
    ]);
    Encore.addEntry('ezplatform-automated-translation-css', [
        path.resolve(__dirname, '../public/admin/scss/auto-translation.scss'),
    ]);
};