Math Chat Render — vendor mathjax fallback

This folder is intended to contain a local copy of the MathJax bundle used as a fallback when the CDN cannot be reached.

How to add MathJax locally:

1. Download the MathJax v4 build you need (for example the 'tex-mml-chtml' bundle) from the MathJax releases or build it following MathJax docs.
2. Place the JS file at: vendor/mathjax/tex-mml-chtml.js (relative to the plugin root).

Notes:
- The plugin will attempt to load the CDN first. If after 3 seconds MathJax is still not available it will inject the local script from the path above.
- Because MathJax releases tend to be large, including it locally may increase plugin size. Consider serving it from your own CDN if possible.
- Optionally set an SRI hash via the filter `mcr_mathjax_sri` in your theme or site plugin to enable subresource integrity checks for the CDN script.

Example to set SRI in your theme's functions.php:

add_filter( 'mcr_mathjax_sri', function(){
    return 'sha384-REPLACE_WITH_ACTUAL_HASH';
});
