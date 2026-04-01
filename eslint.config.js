import js from '@eslint/js';
import typescriptEslint from '@typescript-eslint/eslint-plugin';
import typescriptParser from '@typescript-eslint/parser';
import deprecation from 'eslint-plugin-deprecation';
import globals from 'globals';

export default [
    js.configs.recommended,
    {
        files: ['**/*.ts', '**/*.tsx'],
        plugins: {
            '@typescript-eslint': typescriptEslint,
            'deprecation': deprecation
        },
        languageOptions: {
            parser: typescriptParser,
            ecmaVersion: 2020,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.es2020,
                ...globals.node
            },
            parserOptions: {
                project: './tsconfig.json'
            }
        },
        rules: {
            'deprecation/deprecation': 'warn',
            '@typescript-eslint/no-unused-vars': 'warn',
            '@typescript-eslint/no-explicit-any': 'warn',
            'no-var': 'error',
            'prefer-const': 'error'
        }
    }
];