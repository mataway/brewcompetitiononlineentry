<?php

/**
 * Module:      index.landing.php
 * Description: California Cider Cup brand landing page - the document served at
 *              the site root.
 *
 *              index.php hands off to this file when a request arrives with no
 *              ?section= and no ?msg=, so the competition application itself
 *              (index.pub.php / index.legacy.php) is untouched and still lives
 *              at index.php?section=default.
 *
 *              Rendered from the approved design at bexisrad.my.canva.site.
 *              Presentation lives in css/ccc.css; the About / Join Us copy lives
 *              in pub/landing.pub.php.
 */

// Redirect if directly accessed - bootstrap.php must have run.
if ((!isset($base_url)) || (!isset($_SESSION['contestName']))) {
    header("Location: index.php?section=default");
    exit();
}

$ccc_images_url = $base_url."images/ccc/";

/**
 * The competition pages are section=default. This is deliberately not
 * build_public_url("default", ...): with SEF URLs enabled that helper collapses
 * "default" to the bare site root, which is this landing page.
 */
$ccc_competition_url = $base_url."index.php?section=default";

$ccc_account_url = $logged_in
    ? build_public_url("list", "default", "default", "default", $sef, $base_url)
    : build_public_url("login", "default", "default", "default", $sef, $base_url);

$ccc_contest_name = htmlspecialchars($_SESSION['contestName'], ENT_QUOTES, 'UTF-8');
$ccc_description = "The California Cider Cup celebrates and recognizes great California cider in all forms - from heirloom orchard fruit to modern craft cider.";

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $ccc_contest_name; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($ccc_description, ENT_QUOTES, 'UTF-8'); ?>" />

    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $ccc_images_url; ?>ccc-icon-32.png" />
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo $ccc_images_url; ?>ccc-icon-512.png" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $ccc_images_url; ?>ccc-icon-180.png" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Jost:wght@500;700;800&amp;family=Hanken+Grotesk:wght@300;400;600;700&amp;display=swap" />
    <link rel="stylesheet" type="text/css" href="<?php echo $css_url; ?>ccc.css" />

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo $ccc_contest_name; ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($ccc_description, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="<?php echo $ccc_images_url; ?>ccc-share.jpg" />
    <meta property="og:url" content="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta name="twitter:card" content="summary_large_image" />

</head>

<body class="ccc-landing">

<header class="ccc-header">
    <div class="ccc-wrap">

        <a class="ccc-header-mark" href="#top">
            <img src="<?php echo $ccc_images_url; ?>ccc-mark-light.png" alt="" />
            <span><?php echo $ccc_contest_name; ?></span>
        </a>

        <nav class="ccc-nav" aria-label="Main">
            <a href="#about">About</a>
            <a href="<?php echo $ccc_competition_url; ?>#rules">Rules</a>
            <a href="<?php echo $ccc_competition_url; ?>#entry-info">Entry Info</a>
            <a href="<?php echo $ccc_competition_url; ?>#volunteers">Volunteers</a>
            <a href="#join">Contact</a>
            <a class="ccc-nav-cta" href="<?php echo $ccc_account_url; ?>"><?php echo $logged_in ? "My Account" : "Log In"; ?></a>
        </nav>

    </div>
</header>

<section id="top" class="ccc-hero">

    <div class="ccc-wrap">
        <img class="ccc-hero-logo" src="<?php echo $ccc_images_url; ?>ccc-logo.png" alt="<?php echo $ccc_contest_name; ?>" />
    </div>

    <div class="ccc-wrap ccc-hero-photo-frame">
        <img class="ccc-hero-photo" src="<?php echo $ccc_images_url; ?>ccc-apples.jpg" alt="A crate of freshly picked cider apples" />
    </div>

</section>

<?php include (PUB.'landing.pub.php'); ?>

<footer class="ccc-footer">
    <div class="ccc-wrap">
        <p class="ccc-footer-note">
            <a href="<?php echo $ccc_competition_url; ?>">Competition details &amp; entry</a>
        </p>
        <p class="ccc-footer-mark"><?php echo date("Y"); ?> &ndash; <?php echo $ccc_contest_name; ?></p>
    </div>
</footer>

</body>
</html>
