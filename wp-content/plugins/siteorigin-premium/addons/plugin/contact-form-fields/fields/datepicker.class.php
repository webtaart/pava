<?php

class SiteOrigin_Widget_ContactForm_Field_Datepicker extends SiteOrigin_Widget_ContactForm_Field_Base {

	protected function initialize( $options ) {
		$datetime_options = $options['field']['datetime_options'];
		$datepicker_deps  = array( 'jquery' );

		if ( ! empty( $datetime_options['show_datepicker'] ) ) {
			wp_enqueue_style( 'pikaday', plugin_dir_url( __FILE__ ) . 'css/pikaday.css' );
			wp_enqueue_script(
				'pikaday',
				plugin_dir_url( __FILE__ ) . 'js/pikaday' . SITEORIGIN_PREMIUM_JS_SUFFIX . '.js',
				array(),
				SITEORIGIN_PREMIUM_VERSION
			);
			array_push( $datepicker_deps, 'pikaday' );
		}
		if ( ! empty( $datetime_options['show_timepicker'] ) ) {
			wp_enqueue_style( 'jquery-timepicker', plugin_dir_url( __FILE__ ) . 'css/jquery.timepicker.css' );
			wp_enqueue_script(
				'jquery-timepicker',
				plugin_dir_url( __FILE__ ) . 'js/jquery.timepicker' . SITEORIGIN_PREMIUM_JS_SUFFIX . '.js',
				array( 'jquery' ),
				SITEORIGIN_PREMIUM_VERSION
			);
			array_push( $datepicker_deps, 'jquery-timepicker' );
		}
		wp_enqueue_style( 'so-contactform-datepicker', plugin_dir_url( __FILE__ ) . 'css/so-contactform-datepicker.css' );
		wp_enqueue_script(
			'so-contactform-datepicker',
			plugin_dir_url( __FILE__ ) . 'js/so-contactform-datepicker' . SITEORIGIN_PREMIUM_JS_SUFFIX . '.js',
			$datepicker_deps,
			SITEORIGIN_PREMIUM_VERSION
		);
	}

	protected function render_field( $options ) {
		$datetime_options = $options['field']['datetime_options'];
		$field_id = $options['field_id'];
		$width_class = ! ( empty( $datetime_options['show_datepicker'] ) || empty( $datetime_options['show_timepicker'] ) ) ? 'half-width' : '';
		if ( ! empty( $datetime_options['show_datepicker'] ) ) {
			$datepicker_id = $field_id . '_datepicker';
			$datepicker_label = $datetime_options['datepicker_label'];
			?>
			<div class="datepicker-container<?php echo ' ' . esc_attr( $width_class ) ?>">
				<?php if( ! empty( $datepicker_label ) ) : ?>
					<label for="<?php echo esc_attr( $datepicker_id ) ?>"><?php echo esc_html( $datepicker_label ) ?></label>
				<?php endif; ?>
				<input type="text" id="<?php echo esc_attr( $datepicker_id ) ?>" class="datepicker"/>
			</div>
			<?php
		}

		if ( ! empty( $datetime_options['show_timepicker'] ) ) {
			$timepicker_id = $field_id . '_timepicker';
			$timepicker_label = $datetime_options['timepicker_label'];
			?>
			<div class="timepicker-container<?php echo ' ' . esc_attr( $width_class ) ?>">
				<?php if( ! empty( $timepicker_label ) ) : ?>
					<label for="<?php echo esc_attr( $timepicker_id ) ?>"><?php echo esc_html( $timepicker_label ) ?></label>
				<?php endif; ?>
				<input type="text" id="<?php echo esc_attr( $timepicker_id ) ?>" class="timepicker<?php echo ' ' . esc_attr( $width_class ) ?>"/>
			</div>
			<?php
		}
		?>
		<input type="hidden" name="<?php echo esc_attr( $options['field_name'] ) ?>"
		       id="<?php echo esc_attr( $field_id ) ?>" value="<?php echo esc_attr( $options['value'] ) ?>"
		       class="so-contactform-datetime"/>
		<?php
	}

}
