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

    grunt.registerTask('version', 'Takes the stable tag of the readme over from the plugin header.', function() {
        var pluginFile = 'user-access-manager.php';
        var readmeFile = 'readme.txt';
        var versionMatch = grunt.file.read(pluginFile).match(/^[ \t\/*#@]*Version:(.*)$/m);

        if (versionMatch === null) {
            grunt.fail.fatal('Unable to read the version from ' + pluginFile);
        }

        var version = versionMatch[1].trim();
        var readme = grunt.file.read(readmeFile);
        var updatedReadme = readme.replace(/^Stable tag:.*$/m, 'Stable tag: ' + version);

        if (updatedReadme === readme) {
            grunt.log.writeln('Stable tag is already ' + version + '.');
            return;
        }

        grunt.file.write(readmeFile, updatedReadme);
        grunt.log.writeln('Stable tag set to ' + version + '.');
    });

    // Default task(s).
    grunt.registerTask('default', ['version', 'pot']);
};