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
        include: ['src/typescript/**/*.test.ts'],
        coverage: {
            provider: 'v8',
            include: ['src/typescript/**/*.ts'],
            exclude: ['src/typescript/**/*.test.ts', 'src/typescript/index.ts'],
            reporter: ['lcov', 'text'],
            reportsDirectory: 'coverage',
        },
    },
});
