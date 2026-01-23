import * as esbuild from 'esbuild';

const isDev = process.argv.includes('--dev');

const config = {
    entryPoints: ['resources/js/index.js'],
    bundle: true,
    outfile: 'resources/dist/filament-image-editor.js',
    format: 'esm',
    platform: 'browser',
    target: ['es2020'],
    sourcemap: isDev,
    minify: !isDev,
    external: [],
    define: {
        'process.env.NODE_ENV': isDev ? '"development"' : '"production"',
    },
    loader: {
        '.js': 'js',
    },
};

if (isDev) {
    const ctx = await esbuild.context(config);
    await ctx.watch();
    console.log('Watching for changes...');
} else {
    await esbuild.build(config);
    console.log('Build complete!');
}
