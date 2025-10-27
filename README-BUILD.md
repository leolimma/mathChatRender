How to build the JS handler (esbuild)

Requirements:
- Node.js (>=16 recommended)

Install dev deps and build:

# PowerShell (Windows)
npm install
npm run build

This will bundle `src/mwai-mathjax-handler.mjs` into `js/mwai-mathjax-handler.js` (minified).

You can also run in watch mode during development:

npm run watch

Notes:
- The bundled file will be compatible with browsers; it transpiles top-level await to a safe pattern via esbuild.
- If you prefer another bundler (Rollup/webpack), adapt accordingly.
