<?php
/**
 * Addon Name: Block Animations
 * Description: Adds row and widget animation options.
 * Documentation: https://siteorigin.com/premium-documentation/plugin-addons/block-animations/
 */

class SiteOrigin_Premium_Block_Animations {

	private $hidden_used = false;

	function __construct(){
		add_filter( 'siteorigin_premium_addon_action_links-panels/animations', array( $this, 'action_links' ) );

		// Enqueue and register the scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 5 );

		// Add the style groups
		add_filter( 'siteorigin_panels_widget_style_groups', array( $this, 'style_groups' ) );
		add_filter( 'siteorigin_panels_row_style_groups', array( $this, 'style_groups' ) );

		// The style fields
		add_filter( 'siteorigin_panels_widget_style_fields', array( $this, 'style_fields' ), 10, 3 );
		add_filter( 'siteorigin_panels_row_style_fields', array( $this, 'style_fields' ), 10, 3 );

		// Handle the style fields
		add_filter( 'siteorigin_panels_row_style_attributes', array( $this, 'style_attributes' ), 10, 2 );
		add_filter( 'siteorigin_panels_widget_style_attributes', array( $this, 'style_attributes' ), 10, 2 );

		add_action( 'wp_head', array( $this, 'add_hiding_class' ) );
		// add_action( 'siteorigin_panels_after_render', array( $this, 'add_hiding_class' ) );
		add_action( 'wp_footer', array( $this, 'add_hiding_class' ) );
	}

	static function single(){
		static $single;
		if( empty( $single ) ) {
			$single = new self();
		}
		return $single;
	}

	/**
	 * Add the action links.
	 */
	function action_links( $links ){
		$links[] = '<a href="#" target="_blank">' . __( 'Help', 'siteorigin-premium' ) . '</a>';
		return $links;
	}

	function register_scripts(){
		wp_register_script(
			'siteorigin-premium-panels-animate',
			plugin_dir_url( __FILE__ ) . 'js/animate' . SiteOrigin_Premium_Manager::$js_suffix . '.js',
			array( 'jquery' ),
			SITEORIGIN_PREMIUM_VERSION
		);
	}

	/**
	 * Add the animations style group
	 *
	 * @param $groups
	 */
	function style_groups( $groups ){
		$groups['animations'] = array(
			'name' => __('Animations', 'siteorigin-premium'),
			'priority' => 20
		);
		return $groups;
	}

	/**
	 * Add the animation style fields
	 *
	 * @param $fields
	 */
	function style_fields( $fields, $post_id, $args ){

		$fields['animation_type'] = array(
			'name' => __('Animation', 'siteorigin-premium'),
			'type' => 'select',
			'options' => include plugin_dir_path( __FILE__ ) . 'inc/animation-types.php',
			'group' => 'animations',
			'description' => __('The type of animation for this element.', 'siteorigin-premium'),
			'priority' => 5,
		);

		$animation_events = array(
			'enter' => __( 'Element Enters Screen', 'siteorigin-premium' ),
			'in' => __( 'Element In Screen', 'siteorigin-premium' ),
			'load' => __( 'Page Load', 'siteorigin-premium' ),
		);
		$default_event = 'enter';
		if( !empty( $args['builderType'] ) && $args['builderType'] === 'layout_slider_builder' ) {
			$animation_events[ 'slide_display' ] = __( 'Slide Display', 'siteorigin-premium' );
			$default_event = 'slide_display';
		}

		$fields['animation_event'] = array(
			'name' => __('Animation Event', 'siteorigin-premium'),
			'type' => 'select',
			'options' => $animation_events,
			'default' => $default_event,
			'group' => 'animations',
			'description' => __('The event that triggers the animation.', 'siteorigin-premium'),
			'priority' => 10,
		);

		$fields['animation_screen_offset'] = array(
			'name' => __('Screen Offset', 'siteorigin-premium'),
			'type' => 'text',
			'default' => 0,
			'group' => 'animations',
			'description' => __('How many pixels above the bottom of the screen must the element be before animating in.', 'siteorigin-premium'),
			'priority' => 15,
		);

		$fields['animation_duration'] = array(
			'name' => __('Animation Speed', 'siteorigin-premium'),
			'type' => 'text',
			'default' => 1,
			'group' => 'animations',
			'description' => __('Number of seconds that the incoming animation lasts.', 'siteorigin-premium'),
			'priority' => 20,
		);

		$fields['animation_hide'] = array(
			'name' => __('Hide Before Animation', 'siteorigin-premium'),
			'type' => 'checkbox',
			'group' => 'animations',
			'default' => true,
			'description' => __('Hide the element before animating.', 'siteorigin-premium'),
			'priority' => 25,
		);

		$fields['animation_delay'] = array(
			'name' => __('Animation Delay', 'siteorigin-premium'),
			'type' => 'text',
			'default' => 0,
			'group' => 'animations',
			'description' => __('Number of seconds after the event to start the animation.', 'siteorigin-premium'),
			'priority' => 30,
		);

		return $fields;
	}

	/**
	 * @param $attributes
	 * @param $args
	 *
	 * @return array
	 */
	function style_attributes( $attributes, $args ){

		if( !empty( $args['animation_type'] ) ) {
			// We have an incoming animation

			$attributes['data-so-animation'] = json_encode( array(
				'animation' => $args[ 'animation_type' ],
				'duration' => isset( $args[ 'animation_duration' ] ) ? floatval( $args[ 'animation_duration' ] ) : 1 ,
				'hide' => !empty( $args[ 'animation_hide' ] ) ? 1 : 0,
				'delay' => isset( $args[ 'animation_delay' ] ) ? floatval( $args[ 'animation_delay' ] ) : 0 ,
				'event' => isset( $args[ 'animation_event' ] ) ? $args[ 'animation_event' ] : 'enter' ,
				'offset' => isset( $args[ 'animation_screen_offset' ] ) ? intval( $args[ 'animation_screen_offset' ] ) : 0 ,
			) );

			if( !empty( $args[ 'animation_hide' ] ) ) {
				$this->hidden_used = true;
				$attributes['class'][] = 'panels-animation-hide';
			}

			// We'll need these scripts and styles
			wp_enqueue_script( 'siteorigin-premium-panels-animate' );
			wp_enqueue_style( 'siteorigin-premium-animate' );
		}

		return $attributes;
	}

	function add_hiding_class(){
		static $once = false;
		if( !$once && $this->hidden_used ) {
			$once = true;
			?>
			<script type="text/javascript">
				//<![CDATA[
				document.write('<style type="text/css">.panels-animation-hide{opacity:0}</style>');
				//]]>
			</script>
			<?php
		}
	}

}

SiteOrigin_Premium_Block_Animations::single();