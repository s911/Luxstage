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
  </div>
</footer>
<?php
$whatsapp_url = function_exists('luxstage_whatsapp_url')
    ? luxstage_whatsapp_url('Hello Luxstage, I would like to discuss stage lighting requirements.')
    : '';
?>
<?php if ($whatsapp_url !== '') : ?>
  <a
    class="lux-whatsapp-float"
    href="<?php echo esc_url($whatsapp_url); ?>"
    target="_blank"
    rel="noopener"
    aria-label="<?php esc_attr_e('Chat with us on WhatsApp', 'luxstage'); ?>"
    title="<?php esc_attr_e('WhatsApp', 'luxstage'); ?>"
  >
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path d="M12 2.25a9.75 9.75 0 0 0-8.375 14.75L2.25 21.75l4.875-1.25A9.75 9.75 0 1 0 12 2.25Zm0 17.5a7.7 7.7 0 0 1-3.925-1.075l-.425-.25-2.475.625.675-2.4-.275-.45A7.75 7.75 0 1 1 12 19.75Zm4.1-5.8c-.225-.125-1.325-.65-1.525-.725-.2-.075-.35-.125-.5.125-.15.25-.575.725-.7.875-.125.15-.25.175-.475.05-.225-.125-.95-.35-1.8-1.125-.675-.6-1.125-1.35-1.25-1.575-.125-.225-.012-.35.1-.462.1-.1.225-.262.337-.394.112-.131.15-.225.225-.375.075-.15.037-.281-.019-.406-.056-.125-.5-1.2-.687-1.65-.182-.437-.369-.381-.5-.387h-.425c-.15 0-.394.056-.6.281-.206.225-.788.769-.788 1.875 0 1.106.806 2.175.919 2.325.112.15 1.588 2.425 3.85 3.394.538.231.956.369 1.281.472.538.169 1.028.144 1.415.087.431-.064 1.325-.541 1.512-1.063.188-.522.188-.969.131-1.063-.056-.094-.206-.15-.431-.275Z"></path>
    </svg>
    <span><?php esc_html_e('WhatsApp', 'luxstage'); ?></span>
  </a>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
