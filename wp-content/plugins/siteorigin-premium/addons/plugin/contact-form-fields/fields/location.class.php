<?php

class SiteOrigin_Widget_ContactForm_Field_Location extends SiteOrigin_Widget_ContactForm_Field_Base {

	protected function initialize( $options ) {
		wp_enqueue_style(
			'so-contactform-location',
			plugin_dir_url( __FILE__ ) . 'css/so-contactform-location.css'
		);
		wp_enqueue_script(
			'so-contactform-location',
			plugin_dir_url( __FILE__ ) . 'js/so-contactform-location' . SITEORIGIN_PREMIUM_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SITEORIGIN_PREMIUM_VERSION
		);
	}

	protected function render_field( $options ) {
		$location_options = $options['field']['location_options'];
		$location         = empty( $options['value'] ) ? $location_options['default_location'] : $options['value'];
		?>
		<input type="text" name="<?php echo esc_attr( $options['field_name'] ) ?>"
		       id="<?php echo esc_attr( $options['field_id'] ) ?>" value="<?php echo esc_attr( $location ) ?>"
		       class="so-contactform-location-autocomplete sow-text-field"/>
		<?php
		if ( ! empty( $location_options['show_map'] ) ) {
			$map_data = array( 'address' => $location, 'apiKey' => $location_options['gmaps_api_key'] );
			?>
			<div class="so-contactform-location-google-map"
			     id="map-canvas-<?php echo esc_attr( $options['field_id'] ) ?>"
			     style="height:200px;"
			     data-options="<?php echo esc_attr( json_encode( $map_data ) ) ?>">
			</div>
			<?php
		}
	}

}
