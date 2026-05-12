// eslint.config.mjs — extends the shared CWM base.
// Project-specific globals and overrides go in this wrapper, not in the
// upstream base file. Run 'composer sync-configs' to refresh after the
// base is updated; this file is left untouched once present.

import baseConfig from './libraries/vendor/cwm/build-tools/templates/eslint.config.base.mjs';

export default [
    ...baseConfig,
    {
        files: ['**/*.js', '**/*.mjs', '**/*.es6.js'],
        languageOptions: {
            globals: {
                pkg_cwmconnect: 'readonly',
            },
        },
    },
];
