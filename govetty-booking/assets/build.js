const esbuild = require('esbuild');

const watch = process.argv.includes('--watch');

const options = {
  entryPoints: ['src/index.jsx'],
  bundle: true,
  outfile: 'build/booking-app.js',
  format: 'iife',
  target: ['es2018'],
  minify: !watch,
  sourcemap: watch ? 'inline' : false,
  loader: { '.jsx': 'jsx' },
  jsx: 'automatic',
  define: { 'process.env.NODE_ENV': watch ? '"development"' : '"production"' },
  logLevel: 'info',
};

async function run() {
  if (watch) {
    const ctx = await esbuild.context(options);
    await ctx.watch();
    console.log('Watching for changes...');
  } else {
    await esbuild.build(options);
  }
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
