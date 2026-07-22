<?php
/**
 * Bootstrap Luxstage B2B site content after core install.
 * Creates pages, Contact Form 7 forms, activates theme, flushes permalinks.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

$admin_email = getenv('WP_ADMIN_EMAIL');
if (is_string($admin_email) && $admin_email !== '' && is_email($admin_email)) {
    update_option('admin_email', $admin_email);
}

switch_theme('luxstage');

$pages = [
    [
        'title' => 'Contact',
        'slug' => 'contact',
        'content' => (
            "Contact Luxstage via sales@luxstage.com, +86 138 0000 0000, Guangzhou, China.\n\n"
            . "<h2>Visit Us</h2>\n"
            . "<iframe src=\"https://maps.google.com/maps?q=Guangzhou&t=&z=13&ie=UTF8&iwloc=&output=embed\" width=\"100%\" height=\"280\"></iframe>\n"
            . "<p>LinkedIn: https://www.linkedin.com/</p>"
        ),
    ],
    [
        'title' => 'About Us',
        'slug' => 'about-us',
        'content' => (
            "<h2>Brand Story</h2><p>Luxstage serves global B2B lighting integrators with OEM/ODM capability.</p>"
            . "<h2>Factory Capability</h2><p>Lean production lines, QA workflow, and aging test process.</p>"
            . "<h2>Certificates</h2><p>CE, RoHS, UL and project-level compliance support.</p>"
            . "<h2>Video</h2><iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/dQw4w9WgXcQ\" title=\"Luxstage\"></iframe>"
        ),
    ],
];

foreach ($pages as $page) {
    $existing = get_posts([
        'post_type' => 'page',
        'name' => $page['slug'],
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);

    if ($existing) {
        wp_update_post([
            'ID' => $existing[0]->ID,
            'post_title' => $page['title'],
            'post_content' => $page['content'],
            'post_status' => 'publish',
        ]);
        echo "page:updated:{$page['slug']}\n";
        continue;
    }

    $page_id = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $page['title'],
        'post_name' => $page['slug'],
        'post_content' => $page['content'],
    ]);

    if (is_wp_error($page_id)) {
        WP_CLI::error($page_id->get_error_message());
    }

    echo "page:created:{$page['slug']}:{$page_id}\n";
}

if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
    echo "cf7:skipped:not_active\n";
    flush_rewrite_rules(false);
    return;
}

$forms = [
    'luxstage_contact' => [
        'title' => 'Luxstage Contact Form',
        'content' => "[text* your-name placeholder \"Your Name\"]\n[email* your-email placeholder \"Business Email\"]\n[text your-company placeholder \"Company\"]\n[tel your-phone placeholder \"Phone\"]\n[textarea* your-message placeholder \"Message\"]\n[submit \"Send\"]",
    ],
    'luxstage_rfq' => [
        'title' => 'Luxstage RFQ Form',
        'content' => "[text* your-name placeholder \"Your Name\"]\n[email* your-email placeholder \"Business Email\"]\n[text your-company placeholder \"Company\"]\n[text product-sku default:get product_sku]\n[text your-quantity placeholder \"Quantity\"]\n[file attachment limit:10mb filetypes:pdf|doc|docx]\n[textarea* your-message placeholder \"Technical requirements\"]\n[submit \"Submit RFQ\"]",
    ],
    'luxstage_catalog' => [
        'title' => 'Luxstage Catalog Lead Form',
        'content' => "[text* your-name placeholder \"Your Name\"]\n[email* your-email placeholder \"Business Email\"]\n[text your-company placeholder \"Company\"]\n[tel your-phone placeholder \"Phone\"]\n[submit \"Get Catalog\"]",
    ],
    'luxstage_batch' => [
        'title' => 'Luxstage Batch Inquiry Form',
        'content' => "[text* your-name placeholder \"Your Name\"]\n[email* your-email placeholder \"Business Email\"]\n[textarea* product-list placeholder \"List product SKU and quantities\"]\n[submit \"Submit Batch Inquiry\"]",
    ],
];

$recipient = (string) get_option('admin_email');
$from_name = (string) (getenv('LUXSTAGE_MAIL_FROM_NAME') ?: 'Luxstage');
$from_email = (string) (getenv('LUXSTAGE_MAIL_FROM') ?: 'no-reply@luxstage.local');

foreach ($forms as $slug => $data) {
    $posts = get_posts([
        'post_type' => 'wpcf7_contact_form',
        'name' => $slug,
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);

    if ($posts) {
        $form_id = (int) $posts[0]->ID;
        wp_update_post([
            'ID' => $form_id,
            'post_title' => $data['title'],
            'post_content' => $data['content'],
            'post_name' => $slug,
        ]);
    } else {
        $form_id = (int) wp_insert_post([
            'post_type' => 'wpcf7_contact_form',
            'post_status' => 'publish',
            'post_title' => $data['title'],
            'post_content' => $data['content'],
            'post_name' => $slug,
        ]);
    }

    update_post_meta($form_id, '_mail', [
        'active' => true,
        'recipient' => $recipient,
        'sender' => sprintf('%s <%s>', $from_name, $from_email),
        'subject' => '[Luxstage] ' . $data['title'],
        'body' => "From: [your-name] <[your-email]>\nCompany: [your-company]\nPhone: [your-phone]\nProduct SKU: [product-sku]\nQuantity: [your-quantity]\nProduct List: [product-list]\nMessage: [your-message]",
        'additional_headers' => 'Reply-To: [your-email]',
        'attachments' => '[attachment]',
        'use_html' => false,
        'exclude_blank' => false,
    ]);
    update_post_meta($form_id, '_mail_2', ['active' => false]);
    echo "form:{$slug}:{$form_id}\n";
}

$form_pages = [
    'contact' => [
        'title' => 'Contact',
        'content' => "[contact-form-7 title=\"Luxstage Contact Form\"]\n<p>sales@luxstage.com | +86 138 0000 0000</p>\n<iframe src=\"https://maps.google.com/maps?q=Guangzhou&t=&z=13&ie=UTF8&iwloc=&output=embed\" width=\"100%\" height=\"280\"></iframe>\n<p>LinkedIn</p>",
    ],
    'rfq' => [
        'title' => 'RFQ',
        'content' => '[contact-form-7 title="Luxstage RFQ Form"]',
    ],
    'catalog-request' => [
        'title' => 'Catalog Request',
        'content' => "[contact-form-7 title=\"Luxstage Catalog Lead Form\"]\n[luxstage_catalog_returning]",
    ],
    'batch-inquiry' => [
        'title' => 'Batch Inquiry',
        'content' => '[contact-form-7 title="Luxstage Batch Inquiry Form"]',
    ],
];

foreach ($form_pages as $slug => $data) {
    $posts = get_posts([
        'post_type' => 'page',
        'name' => $slug,
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);

    if ($posts) {
        wp_update_post([
            'ID' => $posts[0]->ID,
            'post_title' => $data['title'],
            'post_content' => $data['content'],
            'post_status' => 'publish',
        ]);
    } else {
        wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $data['title'],
            'post_name' => $slug,
            'post_content' => $data['content'],
        ]);
    }

    echo "form-page:{$slug}\n";
}

update_option('blog_public', 1);
update_option('permalink_structure', '/%postname%/');
flush_rewrite_rules(true);

WP_CLI::success('Luxstage site bootstrap completed.');
