module.exports = {
    options: {
        separator: ';'
    },
    admin: {
        src: [
            '<%= paths.js.src %>/charcoal/admin/polyfill.js',
            '<%= paths.js.src %>/charcoal/admin/charcoal.js',
            '<%= paths.js.src %>/charcoal/admin/cache.js',
            '<%= paths.js.src %>/charcoal/admin/component_manager.js',
            '<%= paths.js.src %>/charcoal/admin/feedback.js',
            '<%= paths.js.src %>/charcoal/admin/recaptcha.js',
            '<%= paths.js.src %>/charcoal/admin/widget.js',
            '<%= paths.js.src %>/charcoal/admin/widget/*.js',
            '<%= paths.js.src %>/charcoal/admin/property.js',
            '<%= paths.js.src %>/charcoal/admin/property/*.js',
            '<%= paths.js.src %>/charcoal/admin/property/input/**/*.js',
            '<%= paths.js.src %>/charcoal/admin/template.js',
            '<%= paths.js.src %>/charcoal/admin/template/*.js',
            '<%= paths.js.src %>/charcoal/admin/template/**/*.js',
        ],
        dest: '<%= paths.js.dist %>/charcoal.admin.js'
    },
    elfinder: {
        src: [
            '<%= paths.js.src %>/charcoal/admin/elfinder.js'
        ],
        dest: '<%= paths.elfinder.dist %>/main.js'
    },
    vendors: {
        src: [
            // jQuery
            '<%= paths.npm %>/jquery/dist/jquery.js',

            // jQuery UI
            '<%= paths.npm %>/jquery-ui-bundle/jquery-ui.js',

            // Bootstrap
            '<%= paths.npm %>/bootstrap/dist/js/bootstrap.bundle.js',

            // URL Search Params
            '<%= paths.npm %>/url-search-params/build/url-search-params.js',

            /**
             * Bootstrap Dialog (temporary)
             *
             * @todo Too precarious
             * @see  https://github.com/pYr0x/bootstrap-dialog)
             * @see  https://gist.github.com/dominiclord/49d0a84cca789a5be3c532d8f0bc8b75)
             */
            '<%= paths.npm %>/bootstrap-dialog-temporary/bootstrap-dialog.js',

            // Bootstrap Datepicker
            '<%= paths.npm %>/moment/min/moment.min.js',
            // @todo
            // 'bower_components/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js',

            // BB Map
            '<%= paths.npm %>/beneroch-gmap/assets/scripts/dist/min/gmap.min.js',

            // Bootstrap Select (@see paramono/bootstrap-select)
            '<%= paths.npm %>/bootstrap-select-temporary/dist/js/bootstrap-select.js',
            // '<%= paths.npm %>/bootstrap-select/dist/js/bootstrap-select.js',

            // jQuery MiniColors
            '<%= paths.npm %>/@claviska/jquery-minicolors/jquery.minicolors.min.js',

            // Multiselect Two-sides
            '<%= paths.npm %>/multiselect-two-sides/dist/js/multiselect.min.js',

            // Selectize
            '<%= paths.npm %>/selectize/dist/js/standalone/selectize.min.js',

            // Selectize
            '<%= paths.npm %>/clipboard/dist/clipboard.min.js',

            // jQuery Timeago
            '<%= paths.npm %>/timeago/jquery.timeago.js',
        ],
        dest:      '<%= paths.js.dist %>/charcoal.admin.vendors.js',
        separator: "\n"
    }
};