<?php
/**
 * Taxonomy archive for product categories.
 *
 * Reuses the stage lighting archive template while preselecting
 * the current taxonomy term as the category filter.
 */

$term = get_queried_object();
if ($term instanceof WP_Term && $term->taxonomy === 'product_category' && empty($_GET['product_category'])) {
    $_GET['product_category'] = $term->slug;
}

locate_template('archive-stage_lighting.php', true, true);
