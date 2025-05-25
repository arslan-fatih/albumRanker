<?php
/**
 * JavaScript Build Script
 * This script combines and minifies JavaScript files
 */

// Output directory
$outputDir = 'js/dist';

// Create output directory if it doesn't exist
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// List of JavaScript files to combine
$jsFiles = [
    'js/jquery/jquery.min.js',
    'js/bootstrap/bootstrap.bundle.min.js',
    'js/plugins/owl-carousel/owl.carousel.min.js',
    'js/plugins/magnific-popup/jquery.magnific-popup.min.js',
    'js/plugins/audioplayer/audioplayer.min.js',
    'js/active.js',
    'js/main.js',
    'js/messages.js'
];

// Combine and minify JavaScript
$combinedJS = '';
foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        $js = file_get_contents($file);
        
        // Remove comments
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        $js = preg_replace('/\/\/[^\n\r]*[\n\r]/', '', $js);
        
        // Remove whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        $combinedJS .= $js . ";\n";
    }
}

// Save combined JavaScript
file_put_contents($outputDir . '/main.min.js', $combinedJS);

echo "JavaScript files have been combined and minified successfully!\n"; 