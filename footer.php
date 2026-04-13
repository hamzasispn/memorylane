<footer class="site-footer bg-gradient-bt border-white border-t">
    <div class="footer-inner flex items-center justify-center py-6">
        <p class="footer-copy text-primary text-sm font-medium">
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