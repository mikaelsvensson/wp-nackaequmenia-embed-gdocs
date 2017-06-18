<?php

/*
    Plugin Name: Embed Google Docs Shortcode
    Description: Made specifically for embedding Nacka Equmenia's crisis plans into Wordpress posts using a shortcode.
    Version: 1.0.0
    Author: Mikael Svensson
    Author URI: http://www.nackasmu.se
*/

const NACKASMU_GDOC_BEFORE_CONTENT = <<<HTML
<style type="text/css">
div#nackasmu-gdoc {
}
div#nackasmu-gdoc p.title,
div#nackasmu-gdoc p.subtitle {
    display: none;
}
div#nackasmu-gdoc h1 {
    font-size: 1.5em;
}
div#nackasmu-gdoc h2 {
    font-size: 1.2em;
}
div#nackasmu-gdoc ol li,
div#nackasmu-gdoc ul li {
    padding-top: 0.15em;
    padding-bottom: 0.15em;
}
</style>
<div id="nackasmu-gdoc">
HTML;

const NACKASMU_GDOC_AFTER_CONTENT = <<<HTML
</div>
HTML;

const SIX_HOURS = 60 * 60 * 6;

function nackasmu_gdoc_shortcode($atts)
{
    $atts = shortcode_atts(
        array(
            // The URL to the HTML version of the Google Document:
            "url" => null,
            // The number of seconds the Google Document will be cached by the plugin:
            "cache_seconds" => SIX_HOURS
        ),
        $atts,
        'nackasmu_gdoc'
    );
    $url = $atts['url'];
    $cache_seconds = intval($atts['cache_seconds']);

    $cache_file_path = sprintf('%s/nackasmu_gdoc_shortcode-%s.html', sys_get_temp_dir(), md5($url));

    $cache_hit = file_exists($cache_file_path) && filemtime($cache_file_path) > (time() - $cache_seconds);
    if (!$cache_hit) {
        file_put_contents($cache_file_path, file_get_contents($url));
    }

    $doc_contents = file_get_contents($cache_file_path);

    // Extract actual document content (strip out Google Docs header and footer):
    $pos_start = strpos($doc_contents, '<div id="contents">') + strlen('<div id="contents">');
    $pos_end = strpos($doc_contents, '</div><div id="footer">', $pos_start);
    $doc_contents = substr($doc_contents, $pos_start, $pos_end - $pos_start);

    // Modify Google styles to make sure they only apply within the nackasmu-gdoc <div/>
    $doc_contents = preg_replace('/([}])([a-z]+[.#{])/', '$1#nackasmu-gdoc $2', $doc_contents);

    // Modify Google styles to remove font names
    $doc_contents = preg_replace('/font-family:[^;]+;/', '', $doc_contents);

    // Modify Google styles to remove font sizes
    $doc_contents = preg_replace('/font-size:[^;]+;/', '', $doc_contents);

    // Modify Google styles to remove colors
    $doc_contents = preg_replace('/color:[^;]+;/', '', $doc_contents);

    // Remove empty paragraphs
    $doc_contents = preg_replace('/<p class="[a-z0-9 ]+"><span class="[a-z0-9 ]+"><\/span><\/p>/', '', $doc_contents);

    // Modify Google styles to decrease left margins for bullet lists
    $doc_contents = str_replace('margin-left:36pt;', 'margin-left:10pt;', $doc_contents);
    $doc_contents = str_replace('margin-left:72pt;', 'margin-left:30pt;', $doc_contents);

    $cache_status = $cache_hit ? '<!-- loaded from cache -->' : '<!-- downloaded and cached -->';
    return NACKASMU_GDOC_BEFORE_CONTENT . $cache_status . $doc_contents . NACKASMU_GDOC_AFTER_CONTENT;
}

add_shortcode('nackasmu_gdoc', 'nackasmu_gdoc_shortcode');