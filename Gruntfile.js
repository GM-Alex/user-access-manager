module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({
        pot: {
            options:{
                text_domain: 'user-access-manager', //Your text domain. Produces my-text-domain.pot
                dest: 'languages/', //directory to place the pot file
                keywords: ['gettext', '__'] //functions to look for
            },
            files:{
                src:  [
                    'src/**/*.php',
                    'includes/**/*.php'
                ], //Parse all php files
                expand: true
            }
        }
    });

    // Load the plugin that provides the "uglify" task.
    grunt.loadNpmTasks('grunt-pot');

    // Default task(s).
    grunt.registerTask('default', ['pot']);

};