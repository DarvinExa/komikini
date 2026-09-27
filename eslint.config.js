import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import globals from 'globals';

export default tseslint.config(
    js.configs.recommended,
    ...tseslint.configs.recommended,
    {
        files: ['resources/js/**/*.{ts,tsx}'],
        languageOptions: {
            globals: {
                ...globals.browser,
            },
            parserOptions: {
                ecmaFeatures: {
                    jsx: true,
                },
            },
        },
        rules: {
            '@typescript-eslint/no-explicit-any': 'warn',
        },
    },
    {
        ignores: [
            'vendor/**',
            'public/**',
            'node_modules/**',
            'storage/**',
            'bootstrap/ssr/**',
        ],
    }
);
