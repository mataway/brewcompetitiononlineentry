<?php
/**
 * Module:      landing.pub.php
 * Description: Content sections for the California Cider Cup brand landing page
 *              (index.landing.php) - "About" and "Join Us".
 *
 *              Copy is verbatim from the approved design at
 *              bexisrad.my.canva.site. Presentation lives in css/ccc.css.
 */

// Redirect if directly accessed
if ((!isset($base_url)) || (!isset($ccc_images_url))) {
    header("Location: ../index.php?section=default");
    exit();
}

$ccc_instagram_url = "https://www.instagram.com/calicidercup/";
$ccc_contact_email = "info@californiacidercup.com";

?>

<section id="about" class="ccc-section ccc-section--about">
    <div class="ccc-wrap">
        <div class="ccc-about-grid">

            <div class="ccc-about-copy">
                <h2 class="ccc-h2">About</h2>
                <div class="ccc-rule" aria-hidden="true"></div>
                <p>The California Cider Cup is all about celebrating and recognizing great California cider in all forms.</p>
                <p>We are inclusive, from the orchardist coaxing nuanced flavors from heirloom fruit to the modern ciderist pushing the boundaries of tradition.</p>
                <p>We&rsquo;re creating a friendly competition that showcases the creativity, craft, and character of this state&rsquo;s great cider makers.</p>
            </div>

            <div class="ccc-about-media">
                <img class="ccc-about-photo" src="<?php echo ccc_asset('images/ccc/ccc-poppies.jpg', $base_url); ?>" alt="California poppies against a blue sky" loading="lazy" />
            </div>

        </div>
    </div>
</section>

<section id="join" class="ccc-section ccc-section--join">
    <div class="ccc-wrap">

        <h2 class="ccc-h2">Join Us</h2>
        <div class="ccc-rule" aria-hidden="true"></div>

        <p>If you&rsquo;d like to enter your ciders, sign up for our email list and follow us on social for updates and schedules.</p>
        <p>Ciders will be judged blind by a distinguished panel of cider experts, including Certified Cider Professionals, beverage directors and buyers, writers, cider makers, taproom owners, and other industry professionals.</p>
        <p>If you&rsquo;re one of these industry professionals, email us if you&rsquo;re interested in building the future of California Cider together.</p>

        <div class="ccc-tiles">

            <a class="ccc-tile" href="<?php echo $ccc_instagram_url; ?>" target="_blank" rel="noopener" aria-label="Follow the California Cider Cup on Instagram">
                <span class="ccc-tile-label">Follow Us</span>
                <span class="ccc-tile-icon ccc-tile-icon--social">
                    <span><img src="<?php echo ccc_asset('images/ccc/ccc-mark.png', $base_url); ?>" alt="" /></span>
                </span>
            </a>

            <a class="ccc-tile" href="mailto:<?php echo $ccc_contact_email; ?>" aria-label="Email <?php echo $ccc_contact_email; ?>">
                <span class="ccc-tile-label">Contact Us</span>
                <span class="ccc-tile-icon">
                    <svg viewBox="0 0 48 34" aria-hidden="true" focusable="false">
                        <rect x="1" y="1" width="46" height="32" rx="3" fill="#593624"/>
                        <path d="M3 4 24 20 45 4" fill="none" stroke="#E4D3AF" stroke-width="3.5" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                </span>
            </a>

        </div>

    </div>
</section>
