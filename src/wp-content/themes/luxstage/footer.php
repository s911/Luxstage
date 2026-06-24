<?php
/**
 * Theme footer.
 */
?>
<footer class="fw-b2b-footer">
  <div class="fw-b2b-container lux-footer__grid">
    <div>
      <strong><?php bloginfo('name'); ?></strong>
      <p><?php esc_html_e('Professional stage lighting manufacturer for global B2B buyers.', 'luxstage'); ?></p>
      <small>&copy; <?php echo esc_html(date_i18n('Y')); ?> <?php bloginfo('name'); ?></small>
    </div>
    <address>
      <a href="mailto:sales@luxstage.com">sales@luxstage.com</a><br>
      <a href="tel:+8613800000000">+86 138 0000 0000</a><br>
      <span><?php esc_html_e('Guangzhou, China', 'luxstage'); ?></span>
    </address>
    <nav aria-label="<?php esc_attr_e('Social links', 'luxstage'); ?>">
      <a href="https://www.facebook.com/" rel="noopener" target="_blank">Facebook</a>
      <a href="https://www.linkedin.com/" rel="noopener" target="_blank">LinkedIn</a>
      <a href="https://www.youtube.com/" rel="noopener" target="_blank">YouTube</a>
    </nav>
    <?php if (function_exists('pll_the_languages')) : ?>
      <div class="lux-footer__lang" aria-label="<?php esc_attr_e('Language switcher', 'luxstage'); ?>">
        <?php
        $footer_language_links = pll_the_languages([
            'dropdown' => 0,
            'show_flags' => 0,
            'show_names' => 1,
            'hide_if_no_translation' => 0,
            'echo' => 0,
        ]);
        echo is_string($footer_language_links) ? $footer_language_links : '';
        ?>
      </div>
    <?php endif; ?>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
