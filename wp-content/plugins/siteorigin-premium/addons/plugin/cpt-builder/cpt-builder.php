<?php
/**
 * Addon Name: Custom Post Type Builder
 * Description: Build custom post types with reusable Page Builder layouts.
 * Documentation: https://siteorigin.com/premium-documentation/plugin-addons/custom-post-type-builder/
 */

/**
 * Class SiteOrigin_Panels_CPT_Builder
 */
class SiteOrigin_Premium_Post_Type_Builder {

	const POST_TYPE = 'so_custom_post_type';

	function __construct(){
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_custom_post_types' ) );

		add_filter( 'siteorigin_panels_settings', array( $this, 'enable_page_builder' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_filter( 'siteorigin_panels_post_builder_type', array( $this, 'post_builder_type' ), 15, 2 );
		add_filter( 'siteorigin_panels_builder_supports', array( $this, 'builder_supports' ), 10, 3 );

		// This is to modify Page Builder style sections for the custom post type interface
		add_filter( 'siteorigin_panels_widget_style_groups', array($this, 'widget_style_groups'), 10, 3 );
		add_filter( 'siteorigin_panels_widget_style_fields', array($this, 'widget_style_fields'), 10, 3 );

		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );

		// Merge panels data from the
		add_filter( 'siteorigin_panels_data_pre_save', array( $this, 'panels_data_pre_save_filter' ), 10, 2 );
		add_filter( 'siteorigin_panels_data', array( $this, 'panels_data_filter' ), 10, 2 );

		// Integrate with SiteOrigin Page Settings
		add_filter( 'siteorigin_page_settings_values', array( $this, 'siteorigin_page_settings' ), 10, 3 );
	}

	/**
	 * Get the single instance
	 *
	 * @return SiteOrigin_Panels_CPT_Builder
	 */
	static function single(){
		static $single;
		if( empty($single) ) {
			$single = new self();
		}
		return $single;
	}

	/**
	 * Register the post type that represents the custom post types. Very meta.
	 */
	function register_post_type(){
		if( current_user_can( 'manage_options' ) ) {
			register_post_type( self::POST_TYPE, array(
				'label' => __( 'Post Types', 'siteorigin-panels' ),
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'supports' => array( 'title', 'editor', 'so-page-settings' ),
				'show_in_menu' => 'tools.php',
			) );
		}
	}

	/**
	 * Get all the registered post types
	 *
	 * @return array|bool|mixed|null|object
	 */
	function get_post_types(){
		$post_types = wp_cache_get( 'post_types', 'so_post_type_builder' );

		if( $post_types === false ) {
			global $wpdb;
			$post_types = $wpdb->get_results( "
				SELECT ID, post_title, post_name
				FROM {$wpdb->posts}
				WHERE
					post_type = '" . self::POST_TYPE . "'
					AND post_status = 'publish'
			" );

			$return = array();
			foreach( $post_types as &$post_type ) {
				$post_type->post_type_settings = get_post_meta( $post_type->ID, 'siteorigin_post_type_settings', true );
				$slug = $post_type->post_type_settings['slug'];

				if( empty( $slug ) ) {
					$slug = $post_type->post_name;
				}

				// Skip if this slug exists already
				if( empty( $slug ) || isset( $return[ $slug ] ) ) continue;

				$return[ $slug ] = $post_type;
			}
			$post_types = $return;

			if( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
				wp_cache_set( 'post_types', $post_types, 'so_post_type_builder', 86400 );
			}
		}

		return $post_types;
	}

	/**
	 * Register all the custom post types we're using
	 */
	function register_custom_post_types(){
		// Now we can process the post types
		$post_types = $this->get_post_types();

		if( !empty( $post_types ) && is_array( $post_types ) ) {
			foreach( $post_types as $slug => $type ) {
				global $wp_post_types, $wp_taxonomies;

				$settings = $type->post_type_settings;

				// Skip if this already exists.
				if( !empty( $wp_post_types[ $slug ] ) ) continue;

				// Register the post types
				register_post_type( $slug, array(
					'labels' => array(
						'name' => empty( $settings['labels']['plural']) ? $type->post_title : $settings['labels']['plural'],
						'singular_name' => empty( $settings['labels']['single']) ? $type->post_title : $settings['labels']['single'],
					),
					'public' => true,
					'has_archive' => $settings['has_archive'],
					'supports' => array_merge( $settings['supports'], array( 'editor', 'so-cpt-builder' ) ),
					'menu_icon' => $settings['icon'],
				) );

				// Now add the taxonomies

				if( !empty( $settings['taxonomy'] ) ) {

					foreach( $settings['taxonomy'] as $taxonomy ) {
						if( empty( $taxonomy['slug'] ) ) continue;
						if( empty( $wp_taxonomies[ $taxonomy['slug'] ] ) ) {
							// We'll register a new taxonomy
							register_taxonomy(
								$taxonomy['slug'],
								$slug,
								array(
									'label' => $taxonomy['label'],
									'hierarchical' => $taxonomy[ 'hierarchical' ]
								)
							);
						}
						else {
							// We'll just use the existing taxonomy
							register_taxonomy_for_object_type( $taxonomy['slug'], $slug );
						}
					}
				}

			}
		}

	}

	/**
	 * Enable Page Builder for custom post types.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	function enable_page_builder( $settings ){
		if( empty( $settings['post-types'] ) ) {
			$settings['post-types'] = array();
		}

		$slugs = array();
		foreach( $this->get_post_types() as $slug => $type ) {
			$slugs[] = $slug;
		}

		$settings['post-types'] = array_unique(
			array_merge( $settings['post-types'], $slugs, array( self::POST_TYPE ) )
		);

		return $settings;
	}

	function post_builder_type( $type, $post ){
		if( $post->post_type == self::POST_TYPE ) {
			$type = 'post_type_builder';
		}

		return $type;
	}

	/**
	 * Add the style groups required for CPT
	 *
	 * @param $groups
	 *
	 * @return mixed
	 */
	function widget_style_groups( $groups, $post_id, $args ) {
		// Ignore this when not displaying the Post Type Builder
		if( ( !empty( $args['builderType'] ) && $args['builderType'] === 'post_type_builder' ) || $args === false ) {
			$groups['so_cpt'] = array(
				'name' => __('Custom Post Type', 'siteorigin-panels'),
				'priority' => 1
			);
		}

		return $groups;
	}

	/**
	 * Add the styles fields required for the CPT interface
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	function widget_style_fields($fields, $post_id, $args ) {
		// Ignore this when not displaying the Post Type Builder
		if( ( !empty( $args['builderType'] ) && $args['builderType'] === 'post_type_builder' ) || $args === false ) {

			$fields['so_cpt_readonly'] = array(
				'name' => __( 'Read Only Widget', 'siteorigin-panels' ),
				'label' => __( 'Read Only', 'siteorigin-premium' ),
				'type' => 'checkbox',
				'group' => 'so_cpt',
				'description' => __('This widget will be the same across all instances of this post type.', 'siteorigin-panels'),
				'priority' => 10,
			);

		}
		return $fields;
	}

	function builder_supports( $supports, $post, $panels_data ){

		$post_types = $this->get_post_types();

		if(
			!empty( $post_types ) && is_array( $post_types ) &&
			isset( $post->post_title ) && in_array( $post->post_type, array_keys( $post_types ) )
		) {
			$supports = array(
				'addRow' => false,
				'editRow' => false,
				'deleteRow' => false,
				'moveRow' => false,

				'addWidget' => false,
				'editWidget' => true,
				'deleteWidget' => false,
				'moveWidget' => false,

				'revertToEditor' => false,
				'prebuilt' => false,
			);
		}

		return $supports;
	}

	/**
	 * Handle saving the post type data
	 *
	 * @param $post_id
	 * @param $post
	 */
	function save_post( $post_id, $post ){
		if(
			$post->post_type == self::POST_TYPE &&
			! empty( $_POST['so_post_type_settings'] ) &&
			! empty( $_POST['_so_cpt_nonce'] ) &&
			wp_verify_nonce( $_POST['_so_cpt_nonce'], 'save_post_type_settings' )
		) {
			$form = $this->get_form();

			$old_settings = get_post_meta( $post_id, 'siteorigin_post_type_settings', true );
			$settings = stripslashes_deep( $_POST['so_post_type_settings'] );
			unset( $settings['_sow_form_id'] );

			$settings = $form->update( $settings, $old_settings );
			$settings['slug'] = wp_unique_post_slug( sanitize_title( $settings['slug'] ), $post_id, 'publish', self::POST_TYPE, false );

			update_post_meta( $post_id, 'siteorigin_post_type_settings', $settings );

			// Clear all the caches
			wp_cache_delete( 'post_types', 'so_post_type_builder' );
			global $wp_rewrite;
			$wp_rewrite->flush_rules( true );
		}
	}

	/**
	 * Process the post type widgets before storing them in the database.
	 *
	 * @param $panels_data
	 * @param $post
	 * @return mixed
	 */
	function panels_data_pre_save_filter( $panels_data, $post ){
		if( $post->post_type == self::POST_TYPE && !empty( $panels_data['widgets'] ) && is_array( $panels_data['widgets'] ) ) {
			$read_only = array();

			foreach( $panels_data['widgets'] as & $widget ) {
				if( !empty( $widget['panels_info']['style']['so_cpt_readonly'] ) && !empty( $widget['panels_info']['widget_id'] ) ) {
					$read_only[ $widget['panels_info']['widget_id'] ] = $widget;
				}
			}

			if( !empty($read_only) ) {
				update_post_meta( $post->ID, 'panels_read_only_widgets', $read_only );
			}
		}

		return $panels_data;
	}

	/**
	 * Filter the panels_data to add in the data from the post type
	 */
	function panels_data_filter( $panels_data, $post ){
		if( empty( $post ) ) return $panels_data;

		$post = get_post( $post );
		if( empty( $post ) ) return $panels_data;

		$post_types = $this->get_post_types();
		if( post_type_supports( $post->post_type, 'so-cpt-builder' ) && !empty( $post_types[ $post->post_type ] ) ) {
			$post_type = $post_types[ $post->post_type ];
			$cpt_panels_data = get_post_meta( $post_type->ID, 'panels_data', true );

			if( empty( $cpt_panels_data ) ) return $panels_data;
			if( empty( $cpt_panels_data['widgets'] ) ) return $panels_data;

			$post_widgets = array();
			if( ! empty( $panels_data['widgets'] ) ) {
				foreach( $panels_data['widgets'] as $widget ) {
					if( empty( $widget['panels_info']['widget_id'] ) ) continue;
					$post_widgets[ $widget['panels_info']['widget_id'] ] = $widget;
				}
			}

			foreach( $cpt_panels_data['widgets'] as & $widget ) {
                if( isset( $widget['panels_info']['style']['so_cpt_readonly'] )  ) {
					$widget['panels_info']['read_only'] = $widget['panels_info']['style']['so_cpt_readonly'];
				}

				if(
					empty( $widget['panels_info']['read_only'] ) &&
					!empty( $widget['panels_info']['widget_id'] ) &&
					isset( $post_widgets[ $widget['panels_info']['widget_id'] ] )
				) {
					// Replace this with the widget from the post
					$old_panels_info = $widget['panels_info'];
					$widget = $post_widgets[ $widget['panels_info']['widget_id'] ];
					$widget[ 'panels_info' ] = $old_panels_info;
				}
			}

			return $cpt_panels_data;
		}

		return $panels_data;
	}

	/**
	 * Register the meta boxes for the post
	 */
	function add_meta_boxes( ){
		add_meta_box(
			'so-post-type-settings',
			__( 'Post Type Settings', 'siteorigin-premium' ),
			array( $this, 'meta_box_callback' ),
			self::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Get the settings form.
	 *
	 * @return SiteOrigin_Premium_Form
	 */
	function get_form(){
		return SiteOrigin_Premium_Manager::single()->get_form(
			'so_post_type_settings',
			array(
				'supports' => array(
					'type' => 'checkboxes',
					'label' => __( 'Post Supports', 'siteorigin-premium' ),
					'multiple' => true,
					'options' => array(
						'title' => __( 'Title', 'siteorigin-premium' ),
						'author' => __( 'Author', 'siteorigin-premium' ),
						'thumbnail' => __( 'Thumbnail', 'siteorigin-premium' ),
						'excerpt' => __( 'Excerpt', 'siteorigin-premium' ),
						'trackbacks' => __( 'Trackbacks', 'siteorigin-premium' ),
						'custom-fields' => __( 'Custom Fields', 'siteorigin-premium' ),
						'comments' => __( 'Comments', 'siteorigin-premium' ),
						'revisions' => __( 'Revisions', 'siteorigin-premium' ),
						'page-attributes' => __( 'Page Attributes', 'siteorigin-premium' ),
						'post-formats' => __( 'Post Formats', 'siteorigin-premium' ),
					),
					'default' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
				),
				'slug' => array(
					'type' => 'text',
					'label' => __( 'Slug', 'siteorigin-premium' ),
					'description' => __( 'Used in the post type URL. This must be unique across all post types. Do not change this after creating the post type.' ),
					'sanitize' => array( $this, 'sanitize_reserved_post_types' ),
				),
				'has_archive' => array(
					'type' => 'checkbox',
					'label' => __( 'Has archive pages', 'siteorigin-premium' ),
					'default' => true,
				),
				'icon' => array(
					'type' => 'icon',
					'label' => __( 'Admin Icon', 'siteorigin-premium' ),
					'icons_callback' => array( $this, 'dashicons_callback' ),
				),
				'labels' => array(
					'type' => 'section',
					'label' => __( 'Labels', 'siteorigin-premium' ),
					'fields' => array(
						'single' => array(
							'type' => 'text',
							'label' => __( 'Singular', 'siteorigin-premium' ),
						),
						'plural' => array(
							'type' => 'text',
							'label' => __( 'Plural', 'siteorigin-premium' ),
						),
					),
				),
				'description' => array(
					'type' => 'textarea',
					'label' => __( 'Description', 'siteorigin-premium' )
				),
				'taxonomy' => array(
					'type' => 'repeater',
					'label' => __('Taxonomies', 'siteorigin-premium'),
					'fields' => array(
						'label' => array(
							'type' => 'text',
							'label' => __( 'Label', 'siteorigin-premium' ),
						),
						'slug' => array(
							'type' => 'text',
							'label' => __( 'Slug', 'siteorigin-premium' ),
						),
						'hierarchical' => array(
							'type' => 'checkbox',
							'label' => __( 'Hierarchical', 'siteorigin-premium' ),
						),
					),
				)
			)
		);
	}

	/**
	 * Display the meta box callback.
	 *
	 * @param $post
	 * @param $args
	 */
	function meta_box_callback( $post, $args ){
		$form = $this->get_form();

		$cpt_builder = SiteOrigin_Premium_Post_Type_Builder::single();
		$settings = $cpt_builder->get_post_settings( $post->ID );
		$form->form( $settings );
		wp_nonce_field( 'save_post_type_settings', '_so_cpt_nonce' );
	}

	/**
	 * Get the post settings for this post
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	function get_post_settings( $post_id ){
		$settings = get_post_meta( $post_id, 'siteorigin_post_type_settings', true );
		if( empty( $settings ) ) {
			$settings = array();
		}
		return $settings;
	}

	function dashicons_callback( ) {
		return array(
			'dashicons' => array(
				'name' => __( 'Dashicons', 'siteorigin-premium' ),
				'style_uri' => plugin_dir_url(__FILE__) . 'dashicons/style.css',
				'icons' => include plugin_dir_path( __FILE__ ) . 'dashicons/icons.php'
			)
		);
	}

	function sanitize_reserved_post_types( $post_type, $old_value ) {
		static $reserved = array(
			'post',
			'page',
			'attachment',
			'revision',
			'nav_menu_item',
			'action',
			'author',
			'order',
			'theme',
		);

		return ! in_array( $post_type, $reserved ) ? $post_type : $old_value;
	}

	/**
	 * @param $values
	 * @param $type
	 * @param $id
	 *
	 * @return array|mixed
	 */
	function siteorigin_page_settings( $values, $type, $id ){
		if( $type !== 'post' ) return $values;

		$post = get_post( $id );
		$post_types = $this->get_post_types();

		if( isset( $post_types[ $post->post_type ] ) ) {
			$values = get_post_meta( $post_types[ $post->post_type ]->ID, 'siteorigin_page_settings', true );
			if( empty($values) ) $values = array();
		}

		return $values;
	}

}

// Create the initial single instance
SiteOrigin_Premium_Post_Type_Builder::single();