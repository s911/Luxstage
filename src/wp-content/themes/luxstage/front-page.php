<?php
/**
 * Front page template matching the Luxstage B2B PRD layout.
 */

get_header();

$featured_products = new WP_Query([
    'post_type'      => 'stage_lighting',
    'post_status'    => 'publish',
    'posts_per_page' => 6,
]);

$product_categories = get_terms([
    'taxonomy'   => 'product_category',
    'hide_empty' => false,
]);

$rfq_flash = function_exists('luxstage_home_rfq_read_flash') ? luxstage_home_rfq_read_flash() : [];
$rfq_status = (string) ($rfq_flash['status'] ?? '');
$rfq_notice = (string) ($rfq_flash['message'] ?? '');
$rfq_old = is_array($rfq_flash['old'] ?? null) ? $rfq_flash['old'] : [];
$rfq_email = (string) ($rfq_old['email'] ?? '');
$rfq_company = (string) ($rfq_old['company'] ?? '');
$rfq_message = (string) ($rfq_old['message'] ?? '');
$rfq_nonce = wp_create_nonce('luxstage_home_rfq_submit');
?>
<main>
  <section class="lux-hero">
    <div class="fw-b2b-container lux-hero__grid">
      <div>
        <p class="lux-eyebrow"><?php esc_html_e('Professional Stage Lighting Manufacturer', 'luxstage'); ?></p>
        <h1><?php esc_html_e('Reliable OEM/ODM Stage Lighting for Global Events', 'luxstage'); ?></h1>
        <p>
          <?php esc_html_e('Luxstage supplies moving heads, LED pars, strobes, effect lights, follow spots, and laser systems for rental companies, event contractors, and lighting integrators.', 'luxstage'); ?>
        </p>
        <div class="lux-actions">
          <a class="lux-button lux-button--primary" href="<?php echo esc_url(home_url('/products/')); ?>">
            <?php esc_html_e('View Products', 'luxstage'); ?>
          </a>
          <a class="lux-button lux-button--secondary" href="<?php echo esc_url(home_url('/downloads/catalogs/')); ?>">
            <?php esc_html_e('Get Catalog', 'luxstage'); ?>
          </a>
        </div>
      </div>
      <div class="lux-hero__panel" aria-label="<?php esc_attr_e('Key advantages', 'luxstage'); ?>">
        <div><strong>CE / RoHS / UL</strong><span><?php esc_html_e('Certification Ready', 'luxstage'); ?></span></div>
        <div><strong>OEM / ODM</strong><span><?php esc_html_e('Custom Manufacturing', 'luxstage'); ?></span></div>
        <div><strong>Fast Delivery</strong><span><?php esc_html_e('Export-focused Supply Chain', 'luxstage'); ?></span></div>
      </div>
    </div>
  </section>

  <section class="fw-b2b-container lux-section">
    <div class="lux-section__header">
      <p class="lux-eyebrow"><?php esc_html_e('Products', 'luxstage'); ?></p>
      <h2><?php esc_html_e('Stage Lighting Product Lines', 'luxstage'); ?></h2>
      <a href="<?php echo esc_url(home_url('/products/')); ?>"><?php esc_html_e('All products', 'luxstage'); ?></a>
    </div>

    <?php if (!is_wp_error($product_categories) && $product_categories) : ?>
      <div class="lux-chip-grid">
        <?php foreach ($product_categories as $category) : ?>
          <a class="lux-chip" href="<?php echo esc_url(get_term_link($category)); ?>">
            <?php echo esc_html($category->name); ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($featured_products->have_posts()) : ?>
      <div class="fw-b2b-grid lux-product-grid">
        <?php while ($featured_products->have_posts()) : $featured_products->the_post(); ?>
          <article <?php post_class('fw-b2b-card lux-card'); ?>>
            <a href="<?php the_permalink(); ?>">
              <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('medium_large'); ?>
              <?php else : ?>
                <div class="lux-card__placeholder"><?php esc_html_e('Luxstage', 'luxstage'); ?></div>
              <?php endif; ?>
              <h3><?php the_title(); ?></h3>
            </a>
            <dl class="lux-spec-strip">
              <div><dt><?php esc_html_e('Power', 'luxstage'); ?></dt><dd><?php echo esc_html((string) luxstage_field('wattage')); ?></dd></div>
              <div><dt><?php esc_html_e('Source', 'luxstage'); ?></dt><dd><?php echo esc_html((string) luxstage_field('light_source_type')); ?></dd></div>
              <div><dt><?php esc_html_e('DMX', 'luxstage'); ?></dt><dd><?php echo esc_html((string) luxstage_field('channels')); ?></dd></div>
            </dl>
            <a class="lux-text-link" href="<?php the_permalink(); ?>"><?php esc_html_e('View details', 'luxstage'); ?></a>
          </article>
        <?php endwhile; ?>
      </div>
      <?php wp_reset_postdata(); ?>
    <?php else : ?>
      <div class="lux-empty-state">
        <h3><?php esc_html_e('Demo products are not loaded yet.', 'luxstage'); ?></h3>
        <p><?php esc_html_e('Run the seed script to create 10 stage lighting products for layout and data testing.', 'luxstage'); ?></p>
      </div>
    <?php endif; ?>
  </section>

  <section class="lux-trust">
    <div class="fw-b2b-container">
      <div class="lux-trust__grid">
        <div>
          <p class="lux-eyebrow"><?php esc_html_e('Certificates & Trust', 'luxstage'); ?></p>
          <h2><?php esc_html_e('Built for international B2B procurement', 'luxstage'); ?></h2>
          <p><?php esc_html_e('Support CE, UL, RoHS, ETL and FCC documentation requirements, with export-ready product catalogs and technical parameter sheets.', 'luxstage'); ?></p>
        </div>
        <ul class="lux-badges">
          <li>CE</li>
          <li>RoHS</li>
          <li>UL</li>
          <li>ETL</li>
          <li>FCC</li>
        </ul>
      </div>

      <div class="lux-why">
        <div class="lux-why__head">
          <p class="lux-eyebrow"><?php esc_html_e('Why Choose Luxstage', 'luxstage'); ?></p>
          <h3><?php esc_html_e('Factory strength that reduces project risk', 'luxstage'); ?></h3>
        </div>
        <div class="lux-why__grid">
          <article class="lux-why__card">
            <span class="lux-why__value">20+ Years</span>
            <h4><?php esc_html_e('R&D and manufacturing experience', 'luxstage'); ?></h4>
            <p><?php esc_html_e('Long-term engineering accumulation for stable optics, thermal design, and control systems.', 'luxstage'); ?></p>
          </article>
          <article class="lux-why__card">
            <span class="lux-why__value">100%</span>
            <h4><?php esc_html_e('Full-load burn-in testing', 'luxstage'); ?></h4>
            <p><?php esc_html_e('Every fixture is aged and stress-tested before shipment to improve consistency on live projects.', 'luxstage'); ?></p>
          </article>
          <article class="lux-why__card">
            <span class="lux-why__value">2 Years</span>
            <h4><?php esc_html_e('Warranty and after-sales support', 'luxstage'); ?></h4>
            <p><?php esc_html_e('Structured support process with technical documents and response for export-market customers.', 'luxstage'); ?></p>
          </article>
        </div>
      </div>
    </div>
  </section>

  <section class="fw-b2b-container lux-section lux-rfq">
    <div class="lux-rfq__copy">
      <p class="lux-eyebrow"><?php esc_html_e('RFQ', 'luxstage'); ?></p>
      <h2><?php esc_html_e('Need pricing, catalog, or OEM details?', 'luxstage'); ?></h2>
      <p><?php esc_html_e('Send a product inquiry and our sales team will follow up with specifications, MOQ, delivery plan, and quotation.', 'luxstage'); ?></p>
    </div>
    <div class="lux-rfq__form-wrap">
      <?php if ($rfq_status === 'success') : ?>
        <div class="lux-rfq__success" role="status" aria-live="polite">
          <svg class="lux-rfq__success-icon" viewBox="0 0 52 52" aria-hidden="true" focusable="false">
            <circle class="lux-rfq__success-circle" cx="26" cy="26" r="24" fill="none"></circle>
            <path class="lux-rfq__success-check" fill="none" d="M14 27l8 8 16-16"></path>
          </svg>
          <h3><?php esc_html_e('Thank You for Your Inquiry!', 'luxstage'); ?></h3>
          <p><?php esc_html_e('Our sales team will review your specifications and contact you via email within 24 hours.', 'luxstage'); ?></p>
        </div>
      <?php else : ?>
        <?php if ($rfq_notice !== '') : ?>
          <div class="lux-rfq__notice <?php echo esc_attr('is-' . ($rfq_status !== '' ? $rfq_status : 'warning')); ?>">
            <?php echo esc_html($rfq_notice); ?>
          </div>
        <?php endif; ?>
        <form class="lux-rfq__form" method="post" action="">
          <input type="hidden" name="luxstage_home_rfq_submit" value="1">
          <input type="hidden" name="luxstage_home_rfq_nonce" value="<?php echo esc_attr($rfq_nonce); ?>">
          <div class="lux-rfq__honeypot">
            <label for="lux-rfq-website"><?php esc_html_e('Website', 'luxstage'); ?></label>
            <input id="lux-rfq-website" type="text" name="website" value="">
          </div>
          <label for="lux-rfq-email"><?php esc_html_e('Email', 'luxstage'); ?></label>
          <input
            id="lux-rfq-email"
            class="lux-rfq__input"
            type="email"
            name="lux_rfq_email"
            placeholder="<?php esc_attr_e('your@email.com', 'luxstage'); ?>"
            required
            value="<?php echo esc_attr($rfq_email); ?>"
          >
          <details class="lux-rfq__optional" <?php echo $rfq_company !== '' ? 'open' : ''; ?>>
            <summary><?php esc_html_e('Add company (optional)', 'luxstage'); ?></summary>
            <label for="lux-rfq-company"><?php esc_html_e('Company', 'luxstage'); ?></label>
            <input
              id="lux-rfq-company"
              class="lux-rfq__input"
              type="text"
              name="lux_rfq_company"
              placeholder="<?php esc_attr_e('Your company name', 'luxstage'); ?>"
              value="<?php echo esc_attr($rfq_company); ?>"
            >
          </details>
          <label for="lux-rfq-message"><?php esc_html_e('Your Message', 'luxstage'); ?></label>
          <textarea
            id="lux-rfq-message"
            class="lux-rfq__textarea"
            name="lux_rfq_message"
            placeholder="<?php esc_attr_e('Tell us the products, quantity, and delivery requirements...', 'luxstage'); ?>"
            required
          ><?php echo esc_textarea($rfq_message); ?></textarea>
          <button class="lux-button lux-button--primary lux-rfq__submit" type="submit">
            <?php esc_html_e('Send Inquiry', 'luxstage'); ?>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
get_footer();
