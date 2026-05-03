<?php
/**
 * footer.php
 * Site-wide Footer — Pastimes
 * WEDE6021 POE
 *
 * Include this file at the bottom of every public-facing page.
 */
?>
<!-- ============================================================
     SITE FOOTER
     ============================================================ -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">

            <!-- Brand Column -->
            <div class="footer-brand">
                <div class="footer-logo">
                    <img src="<?php echo isset($root_path) ? $root_path : ''; ?>images/logo.png"
                         alt="Pastimes" height="36"
                         onerror="this.style.display='none'">
                    <span>PAST<em>IMES</em></span>
                </div>
                <p class="footer-tagline">
                    Pre-loved brands. New stories.<br>
                    Sustainable second-hand fashion for everyone.
                </p>
                <div class="footer-social">
                    <a href="#" title="Facebook" aria-label="Facebook">f</a>
                    <a href="#" title="Instagram" aria-label="Instagram">in</a>
                    <a href="#" title="Twitter" aria-label="Twitter">tw</a>
                    <a href="#" title="TikTok" aria-label="TikTok">tk</a>
                </div>
            </div>

            <!-- Shop Column -->
            <div class="footer-col">
                <h4>Shop</h4>
                <ul>
                    <li><a href="<?php echo isset($root_path) ? $root_path : ''; ?>browse.php">All Items</a></li>
                    <li><a href="<?php echo isset($root_path) ? $root_path : ''; ?>browse.php?category=women">Women</a></li>
                    <li><a href="<?php echo isset($root_path) ? $root_path : ''; ?>browse.php?category=men">Men</a></li>
                    <li><a href="<?php echo isset($root_path) ? $root_path : ''; ?>browse.php?category=accessories">Accessories</a></li>
                    <li><a href="<?php echo isset($root_path) ? $root_path : ''; ?>browse.php?sort=newest">New Arrivals</a></li>
                </ul>
            </div>

            <!-- Sell Column -->
            <div class="footer-col">
                <h4>Sell</h4>
                <ul>
                    <li><a href="<?php echo isset($root_path) ? $root_path : ''; ?>sell.php">Become a Seller</a></li>
                    <li><a href="<?php echo isset($root_path) ? $root_path : ''; ?>sell.php">Seller Guidelines</a></li>
                    <li><a href="<?php echo isset($root_path) ? $root_path : ''; ?>sell.php">Fees &amp; Payouts</a></li>
                    <li><a href="<?php echo isset($root_path) ? $root_path : ''; ?>profile.php?tab=listings">Manage Listings</a></li>
                </ul>
            </div>

            <!-- Help Column -->
            <div class="footer-col">
                <h4>Help</h4>
                <ul>
                    <li><a href="#">FAQs</a></li>
                    <li><a href="#">Delivery &amp; Returns</a></li>
                    <li><a href="#">How It Works</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Safety Tips</a></li>
                </ul>
            </div>

            <!-- About Column -->
            <div class="footer-col">
                <h4>About</h4>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Sustainability</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>

            <!-- Contact Column -->
            <div class="footer-col">
                <h4>Contact Us</h4>
                <div class="footer-contact-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328z"/>
                    </svg>
                    <span>021 123 4567</span>
                </div>
                <div class="footer-contact-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383v5.722z"/>
                    </svg>
                    <span>info@pastimes.co.za</span>
                </div>
                <div class="footer-contact-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                    </svg>
                    <span>Cape Town, South Africa</span>
                </div>
            </div>

        </div><!-- /.footer-grid -->
    </div>

    <!-- Footer Bottom Bar -->
    <div style="border-top: 1px solid rgba(255,255,255,0.12);">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Pastimes. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Main JavaScript -->
<script src="<?php echo isset($root_path) ? $root_path : ''; ?>js/main.js"></script>
<?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>
