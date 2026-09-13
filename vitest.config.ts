import { defineConfig } from 'vitest/config';

export default defineConfig({
    resolve: {
        // Allows TypeScript source files to be resolved when imports use .js extensions
        extensionAlias: {
            '.js': ['.ts', '.js'],
        },
    },
    test: {
        environment: 'jsdom',
        // src/Auth/Asset added alongside the pre-existing two roots when
        // keypad-copy-to-clipboard.test.ts was written -- previously
        // nothing under src/Auth/Asset (or the similarly-shaped
        // src/Webshop/*/Asset, src/Backend/Asset, each with their own
        // build:typescript:* esbuild entry point) was ever scanned by
        // vitest at all, so a .test.ts file placed there would have
        // silently never run even locally, let alone in CI.
        include: [
            'src/typescript/**/*.test.ts',
            'src/Asset/*.test.ts',
            'src/Auth/Asset/*.test.ts',
        ],
        coverage: {
            provider: 'v8',
            include: [
                'src/typescript/**/*.ts',
                'src/Asset/*.ts',
                'src/Auth/Asset/*.ts',
            ],
            exclude: [
                'src/typescript/**/*.test.ts',
                'src/typescript/index.ts',
                'src/Asset/*.test.ts',
                'src/Asset/rebuild/**',
                'src/Auth/Asset/*.test.ts',
                'src/Auth/Asset/rebuild/**',
            ],
            reporter: ['lcov', 'text'],
            reportsDirectory: 'coverage',
        },
    },
});
