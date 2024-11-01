<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woosv_Public' ) ) {
	class Woosv_Public {
		protected static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function product_query( $args ) {
			$enable = get_option( 'woosv_enable', 'yes' );

			$args->set( 'woosv_filter', 'yes' );
			$args->set( 'post_type', [ 'product', 'product_variation' ] );

			$meta_query = (array) $args->get( 'meta_query' );

			if ( empty( $enable ) ) {
				$meta_query[] = [
					'relation' => 'OR',
					[
						'key'     => '_variation_description',
						'compare' => 'NOT EXISTS'
					],
					[
						'key'     => 'woosv_enable',
						'value'   => 'enable',
						'compare' => '=='
					],
					[
						'key'     => 'woosv_enable',
						'value'   => 'reverse',
						'compare' => '=='
					],
				];
			} else {
				$meta_query[] = [
					'relation' => 'OR',
					[
						'key'     => '_variation_description',
						'compare' => 'NOT EXISTS'
					],
					[
						'key'     => 'woosv_enable',
						'compare' => 'NOT EXISTS'
					],
					[
						'key'     => 'woosv_enable',
						'value'   => 'default',
						'compare' => '=='
					],
					[
						'key'     => 'woosv_enable',
						'value'   => 'enable',
						'compare' => '=='
					],
				];
			}

			$args->set( 'meta_query', $meta_query );
		}

		public function shortcode_products_query( $args ) {
			$enable = get_option( 'woosv_enable', 'yes' );

			$args['woosv_filter'] = 'yes';
			$args['post_type']    = [ 'product', 'product_variation' ];

			$meta_query = (array) $args['meta_query'];

			if ( empty( $enable ) ) {
				$meta_query[] = [
					'relation' => 'OR',
					[
						'key'     => '_variation_description',
						'compare' => 'NOT EXISTS'
					],
					[
						'key'     => 'woosv_enable',
						'value'   => 'enable',
						'compare' => '=='
					],
					[
						'key'     => 'woosv_enable',
						'value'   => 'reverse',
						'compare' => '=='
					],
				];
			} else {
				$meta_query[] = [
					'relation' => 'OR',
					[
						'key'     => '_variation_description',
						'compare' => 'NOT EXISTS'
					],
					[
						'key'     => 'woosv_enable',
						'compare' => 'NOT EXISTS'
					],
					[
						'key'     => 'woosv_enable',
						'value'   => 'default',
						'compare' => '=='
					],
					[
						'key'     => 'woosv_enable',
						'value'   => 'enable',
						'compare' => '=='
					],
				];
			}

			$args['meta_query'] = $meta_query;

			return $args;
		}

		public function posts_clauses( $clauses, $query ) {
			global $wpdb;
			$enable              = get_option( 'woosv_enable', 'yes' ) === 'yes';
			$hide_parent         = get_option( 'woosv_hide_parent' ) === 'yes';
			$hide_parent_exclude = get_option( 'woosv_hide_parent_exclude' );

			if ( isset( $query->query_vars['woosv_filter'] ) && ( $query->query_vars['woosv_filter'] === 'yes' ) ) {
				$exclude = [];

				if ( $query->is_search() ) {
					if ( $exclude_search_term = get_term_by( 'name', 'exclude-from-search', 'product_visibility' ) ) {
						$exclude[] = $exclude_search_term->term_taxonomy_id;
					}
				} else {
					if ( $exclude_catalog_term = get_term_by( 'name', 'exclude-from-catalog', 'product_visibility' ) ) {
						$exclude[] = $exclude_catalog_term->term_taxonomy_id;
					}
				}

				if ( ! empty( $exclude ) ) {
					$exclude_ids      = implode( ',', $exclude );
					$clauses['where'] .= " AND ( {$wpdb->posts}.post_parent NOT IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ({$exclude_ids}) ) ) ";
				}

				if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'editor' ) ) {
					$clauses['where'] .= " AND ( {$wpdb->posts}.post_parent NOT IN ( SELECT ID FROM {$wpdb->posts} WHERE post_status = 'private' OR post_status = 'draft' OR post_status = 'pending' ) ) ";
				}

				if ( $hide_parent ) {
					$clauses['where'] .= " AND 0 = (select count(*) as totalpart from {$wpdb->posts} as oc_posttb where oc_posttb.post_parent = {$wpdb->posts}.ID and oc_posttb.post_type = 'product_variation' ";

					if ( ! empty( $hide_parent_exclude ) ) {
						$clauses['where'] .= " AND {$wpdb->posts}.ID NOT IN ( " . $hide_parent_exclude . " ) ";
					}

					$clauses['where'] .= " ) ";
				}

				$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} as oc_posttba ON ({$wpdb->posts}.post_parent = oc_posttba.post_id AND oc_posttba.meta_key = 'woosv_enable' ) ";

				if ( $enable ) {
					$clauses['where'] .= " AND ( oc_posttba.meta_value IS NULL OR oc_posttba.meta_value = 'default' OR oc_posttba.meta_value = 'enable' ) ";
				} else {
					$clauses['where'] .= " AND ( oc_posttba.meta_value IS NULL OR oc_posttba.meta_value = 'enable' OR oc_posttba.meta_value = 'reverse' ) ";
				}
			}

			return $clauses;
		}

		function variation_get_name( $name, $product ) {
			if ( ( $custom_name = get_post_meta( $product->get_id(), 'woosv_name', true ) ) && ! empty( $custom_name ) ) {
				return $custom_name;
			}

			return $name;
		}

		function the_title( $post_title, $post_id ) {
			if ( ( $custom_name = get_post_meta( $post_id, 'woosv_name', true ) ) && ! empty( $custom_name ) ) {
				return $custom_name;
			}

			return $post_title;
		}
	}
}
