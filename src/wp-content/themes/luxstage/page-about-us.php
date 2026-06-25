<?php
/**
 * About Us page template.
 */

get_header();
?>
<main class="lux-about">
  <section class="lux-about-hero">
    <div class="fw-b2b-container lux-about-hero__inner">
      <p class="lux-eyebrow"><?php esc_html_e('About Luxstage', 'luxstage'); ?></p>
      <h1><?php esc_html_e('20+ Years of Stage Lighting Manufacturing Excellence', 'luxstage'); ?></h1>
      <p>
        <?php esc_html_e('Welcome to Luxstage, a premier, global stage lighting manufacturer dedicated to illuminating extraordinary moments. Based in Guangzhou, China - the heart of the world entertainment lighting supply chain - we bring over 20 years of specialized expertise in custom stage lighting solutions for concerts, theaters, clubs, and global rental companies.', 'luxstage'); ?>
      </p>
    </div>
  </section>

  <section class="fw-b2b-container lux-about-section">
    <div class="lux-section__header">
      <div>
        <p class="lux-eyebrow"><?php esc_html_e('Why Global Buyers Partner With Us', 'luxstage'); ?></p>
        <h2><?php esc_html_e('Factory strength, engineering depth, and export-ready support', 'luxstage'); ?></h2>
      </div>
    </div>

    <div class="lux-about-grid">
      <article class="lux-about-card">
        <div class="lux-about-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false">
            <path d="M12 2 14.6 7.5l5.9.8-4.3 4.2 1 5.9L12 15.6l-5.2 2.8 1-5.9-4.3-4.2 5.9-.8L12 2Z"></path>
          </svg>
        </div>
        <h3><?php esc_html_e('20 Years of Customization Expertise', 'luxstage'); ?></h3>
        <p><?php esc_html_e('For over two decades, Luxstage has driven innovation in entertainment lighting, designing reliable Beam, Spot, Wash, and Effect fixtures tailored to technical specifications.', 'luxstage'); ?></p>
      </article>

      <article class="lux-about-card">
        <div class="lux-about-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false">
            <path d="M12 2.5 20 6v6c0 5-3.4 8.6-8 9.5C7.4 20.6 4 17 4 12V6l8-3.5Zm3.7 7.1-4.6 4.6-2-2-1.4 1.4 3.4 3.4 6-6-1.4-1.4Z"></path>
          </svg>
        </div>
        <h3><?php esc_html_e('100% Full-Load Burn-In Testing & 2-Year Warranty', 'luxstage'); ?></h3>
        <p><?php esc_html_e('Every fixture leaves our Guangzhou facility only after rigorous full-load burn-in testing, backed by an industry-leading 2-Year Warranty.', 'luxstage'); ?></p>
      </article>

      <article class="lux-about-card">
        <div class="lux-about-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false">
            <path d="M3 21V9l6 3V9l6 3V7h6v14H3Zm4-3h2v-3H7v3Zm5 0h2v-3h-2v3Zm5 0h2v-3h-2v3ZM17 9v2h2V9h-2Z"></path>
          </svg>
        </div>
        <h3><?php esc_html_e('OEM & ODM Services for Global Brands', 'luxstage'); ?></h3>
        <p><?php esc_html_e('From customized branding to full structural and optical design, our in-house R&D team turns concepts into production-ready lighting solutions.', 'luxstage'); ?></p>
      </article>

      <article class="lux-about-card">
        <div class="lux-about-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false">
            <path d="M13 2 4 14h6l-1 8 11-14h-7l0-6Z"></path>
          </svg>
        </div>
        <h3><?php esc_html_e('Rapid Production & Lightning-Fast Response', 'luxstage'); ?></h3>
        <p><?php esc_html_e('Located in Guangzhou, we leverage an elite supply network for fast production cycles, rapid shipping, and technical response within 24 hours.', 'luxstage'); ?></p>
      </article>
    </div>
  </section>

  <section class="fw-b2b-container lux-about-commitment">
    <div>
      <p class="lux-eyebrow"><?php esc_html_e('Our Commitment', 'luxstage'); ?></p>
      <h2><?php esc_html_e('We secure your peace of mind, night after night.', 'luxstage'); ?></h2>
      <p><?php esc_html_e('At Luxstage, we do more than sell fixtures. With comprehensive after-sales guarantees, localized technical support, and reliable hardware, we ensure that your events run flawlessly.', 'luxstage'); ?></p>
    </div>
  </section>

  <section class="fw-b2b-container lux-about-cta">
    <div>
      <p class="lux-eyebrow"><?php esc_html_e('Partner With Luxstage', 'luxstage'); ?></p>
      <h2><?php esc_html_e('Bring world-class brilliance to your stage.', 'luxstage'); ?></h2>
    </div>
    <div class="lux-actions">
      <a class="lux-button lux-button--secondary" href="<?php echo esc_url(home_url('/products/')); ?>">
        <?php esc_html_e('View Products', 'luxstage'); ?>
      </a>
      <a class="lux-button lux-button--primary" href="<?php echo esc_url(home_url('/contact/')); ?>">
        <?php esc_html_e('Contact Us', 'luxstage'); ?>
      </a>
    </div>
  </section>
</main>
<?php
get_footer();
