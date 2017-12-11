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

    $doc_content = nackasmu_get_content($url, $cache_seconds);

    return NACKASMU_GDOC_BEFORE_CONTENT . $doc_content . NACKASMU_GDOC_AFTER_CONTENT;
}

function nackasmu_get_content($url, $cache_seconds)
{
    $cache_file_path = sprintf('%s/nackasmu_gdoc_shortcode-%s.html', sys_get_temp_dir(), md5($url));

    $cache_hit = file_exists($cache_file_path) && filemtime($cache_file_path) > (time() - $cache_seconds);
    if (!$cache_hit) {
        file_put_contents($cache_file_path, mb_convert_encoding(file_get_contents($url), 'HTML-ENTITIES', 'UTF-8'));
    }

    $doc_contents = file_get_contents($cache_file_path);

    // Extract actual document content (strip out Google Docs header and footer):
    $pos_start = strpos($doc_contents, '<div id="contents">') + strlen('<div id="contents">');
    $pos_end = strpos($doc_contents, '</div><div id="footer">', $pos_start);
    $doc_contents = substr($doc_contents, $pos_start, $pos_end - $pos_start);

    // Modify Google styles to make sure they only apply within the nackasmu-gdoc <div/>
    $doc_contents = preg_replace('/([}])([a-z]+[.#{])/', '$1#nackasmu-gdoc $2', $doc_contents);

    // Convert links back from "Google redirects" to regular links.
    $doc_contents = preg_replace('/"https:\/\/www\.google\.com\/url\?q=([a-zA-Z0-9.\/_:=;&-]+)&amp;sa=[^"]+"/', '"$1"', $doc_contents);

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
    $doc_contents = str_replace('margin-left:108pt;', 'margin-left:50pt;', $doc_contents);

    $cache_status = $cache_hit ? '<!-- loaded from cache -->' : '<!-- downloaded and cached -->';
    $doc_contents = $cache_status . $doc_contents;
    return $doc_contents;
}

add_shortcode('nackasmu_gdoc', 'nackasmu_gdoc_shortcode');

function nackasmu_actionplan_precautions_shortcode($atts)
{
    $atts = shortcode_atts(
        array(
            // The post category assigned to posts to scan for precautions to list:
            "post_category" => 'Handlingsplan',
            // The number of seconds the Google Document will be cached by the plugin:
            "cache_seconds" => SIX_HOURS
        ),
        $atts,
        'nackasmu_actionplan_precautions'
    );
    $post_category = $atts['post_category'];
    $cache_seconds = intval($atts['cache_seconds']);

    $args = array(
        'posts_per_page' => 1000,
        'offset' => 0,
        'category' => '',
        'category_name' => $post_category,
//        'orderby' => 'date',
//        'order' => 'DESC',
//        'orderby' => 'title',
//        'order' => 'DESC',
        'include' => '',
        'exclude' => '',
        'meta_key' => '',
        'meta_value' => '',
        'post_type' => 'post',
        'post_mime_type' => '',
        'post_parent' => '',
        'author' => '',
        'author_name' => '',
        'post_status' => 'publish',
        'suppress_filters' => true
    );
    $posts_array = get_posts($args);

    $situtations = array();
    $processed_plans = array();

    $html = "";

    foreach ($posts_array as $post) {
        $matches = array();
        preg_match('/\[nackasmu_gdoc\s+url="(https:\/\/docs.google.com\/document(\/[a-z0-9])+\/[a-zA-Z0-9_-]+\/pub)"\]/', $post->post_content, $matches);

        if (empty($matches)) {
            $html .= sprintf('<!-- No nackasmu_gdoc shortcode found in %s -->', $post->post_title);
            continue;
        }

        $url = $matches[1];
        $contents = nackasmu_get_content($url, $cache_seconds);

        $doc = new DOMDocument();
        $doc->loadHTML("<html><body>" . utf8_decode($contents) . "</body></html>");

        $xpath = new DOMXpath($doc);

        $elements = $xpath->query("//p[span[contains(text(), 'Åtgärder för att')]]");

        if (!is_null($elements)) {
            $processed_plans[] = array(
                "href" => get_post_permalink($post->ID, false, false),
                "title" => $post->post_title
            );
            foreach ($elements as $element) {
                $situation_key = $element->textContent;
                if (strpos($situation_key, 'förebygga') === false) {
                    continue;
                }
                if (!key_exists($situation_key, $situtations)) {
                    $situtations[$situation_key] = array();
                }
                $el = $element;
                while ($el = $el->nextSibling) {
                    if (strpos($el->textContent, 'Åtgärder för att') !== false) {
                        break;
                    }
                    if ($el->tagName == 'ul' || $el->tagName == 'ol') {
                        foreach ($el->childNodes as $childNode) {
                            $situtations[$situation_key][] = $childNode->textContent;
                        }
                    } else {
                        $situtations[$situation_key][] = $el->textContent;
                    }
                }
            }
        } else {
            echo 'No matches';
        }
    }
    foreach ($situtations as $label => $actions) {
        $actions = array_map(function ($action) {
            return preg_match('/[\w\s]{1,20}:/', $action) ? $action : "Övrigt: $action";
        }, array_unique($actions));
        asort($actions);
        $html .= sprintf('<p><strong>%s</strong></p>', $label);
        $html .= sprintf('<ul>%s</ul>', join(array_map(function ($action) {
            return "<li>$action</li>";
        }, array_filter($actions, function ($action) {
            // Only show actions which are at least two characters long (removes actions like "?" and "...")
            return strlen(utf8_encode(trim($action))) >= 4;
        }))));
    }
    $html .= sprintf('<p><small>Åtgärderna har samlats in från de här handlingsplanerna: %s.</small></p>', join(', ', array_map(function ($processed_plan) {
        return sprintf('<a href="%s">%s</a>', $processed_plan['href'], $processed_plan['title']);
    }, $processed_plans)));

    return $html;
}

add_shortcode('nackasmu_actionplan_precautions', 'nackasmu_actionplan_precautions_shortcode');