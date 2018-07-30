module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            default: {
                files: [{
                    src: './client.html',
                    dest: './generated/client.html'
                }]
            }
        },
        replace: {
            pre: {
                src: './generated/client.html',
                overwrite: true, 
                replacements: [{
                    from: /name="ip_(([^"]*)".* value=")[^"]*"/ig,
                    to: 'name="$1));for (uint8_t i = 0; i < 4; i++) out.concat(_cfg.$2[i] + (i < 3 ? DOT : EMPTY_STR));out.concat(F("'
                }, {
                    from: /(name="([^"]*)".* value=")[^"\)]*"/ig,
                    to: '$1));out.concat(_cfg.$2);out.concat(F("'
                }]
            },
            post: {
                src: './generated/client.html',
                overwrite: true, 
                replacements: [{
                    from: /"/g,
                    to: '\\"'
                }, {
                    from: /^/g,
                    to: '\n  out.concat(F('
                }, {
                    from: /$/g,
                    to: '));'
                }, {
                    from: /(\);)/g,
                    to: '$1\n  '
                }, {
                    from: /F\(([^\)]+)\)/g,
                    to: 'F("$1")'
                },
                {
                    from: /([a-z]=)\\"([^\\"]+)\\"/g,
                    to: '$1$2'
                }]
            }
        },
        htmlcompressor: {
            default: {
                files: [{
                    './generated/client.html': './generated/client.html'
                }],
                options: {
                    'compress-js': true,
                    'compress-css': true,
                    'remove-intertag-spaces': true,
                    'remove-js-protocol': true,
                    'remove-script-attr': true,
                    'remove-style-attr': true,
                    'remove-quotes': true
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-htmlcompressor');
    grunt.loadNpmTasks('grunt-text-replace');
    grunt.loadNpmTasks('grunt-contrib-copy');

    grunt.registerTask('default', ['copy', 'replace:pre', 'htmlcompressor', 'replace:post']);

};