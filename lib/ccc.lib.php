<?php

/**
 * Module:      ccc.lib.php
 * Description: California Cider Cup helpers.
 */

/**
 * Append a cache-busting version to a brand asset URL.
 *
 * The host serves everything under /css and /images with
 * "Cache-Control: max-age=315360000, public" (ten years) from an edge cache, so
 * a redeployed file is never re-fetched at the same URL - a CSS or image change
 * ships to the server and stays invisible.
 *
 * Putting the file's modification time in the query string gives each revision
 * its own URL. The HTML that references it is sent no-store/no-cache by
 * index.php, so the new URL is picked up on the very next page view, and the
 * ten-year cache then works in our favour for the version that is current.
 *
 * @param  string $path Path relative to the installation root, e.g. "css/ccc.css".
 * @param  string $base Base URL to prefix, normally $base_url.
 * @return string
 */
function ccc_asset($path, $base) {

    $full = ROOT.$path;
    $version = file_exists($full) ? filemtime($full) : 0;

    return $base.$path."?v=".$version;

}
