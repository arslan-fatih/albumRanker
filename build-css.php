<?php
/**
 * CSS Build Script
 * This script combines and minifies CSS files
 */

// Output directory
$outputDir = 'css/dist';

// Create output directory if it doesn't exist
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// List of CSS files to combine
$cssFiles = [
    'css/bootstrap.min.css',
    'css/font-awesome.min.css',
    'css/classy-nav.css',
    'css/owl.carousel.min.css',
    'css/magnific-popup.css',
    'css/one-music-icon.css',
    'css/audioplayer.css',
    'css/animate.css',
    'style.css'
];

// Combine and minify CSS
$combinedCSS = '';
foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        $css = file_get_contents($file);
        
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove space after colons
        $css = str_replace(': ', ':', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        $combinedCSS .= $css;
    }
}

// Save combined CSS
file_put_contents($outputDir . '/style.min.css', $combinedCSS);

echo "CSS files have been combined and minified successfully!\n"; 