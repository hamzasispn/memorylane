<footer class="site-footer">
    <div class="footer-inner">
        <p class="footer-copy">
            &copy; <?php echo esc_html( date( 'Y' ) ); ?>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <?php bloginfo( 'name' ); ?>
            </a>
        </p>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>