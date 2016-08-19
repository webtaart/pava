<div class="wrap siteorigin-premium-wrap" id="siteorigin-premium-addons">

	<div class="page-header">
		<div class="so-premium-icon-wrapper">
			<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/page-icon.png" class="so-premium-icon" />
		</div>
		<h1><?php _e( 'SiteOrigin Premium Addons', 'siteorigin-premium' ) ?></h1>

		<input type="search" class="addons-search" name="search" placeholder="<?php _e( 'Search Addons', 'siteorigin-premium' ) ?>" />

		<ul class="page-sections">
			<?php
			$sections = array(
				'' => __( 'All Addons', 'siteorigin-premium' ),
				'plugin' => __( 'Plugin Addons', 'siteorigin-premium' ),
				'theme' => __( 'Theme Addons', 'siteorigin-premium' ),
			);
			foreach( $sections as $section_id => $section_title ) {
				?>
				<li <?php if( $filter == $section_id ) echo 'class="active-section"' ?>>
					<a href="#" data-section="<?php echo esc_attr( $section_id ) ?>">
						<?php echo esc_html( $section_title ) ?>
					</a>
				</li>
				<?php
			}
			?>
		</ul>

	</div>

	<div id="addons-list" data-action-url="<?php echo esc_url( $action_url ) ?>">

		<?php foreach( $addons as $section_id => $section_addons ): ?>
			<?php foreach( $section_addons as $addon ) : ?>
				<div class="so-addon-wrap">
					<div
						class="so-addon so-addon-is-<?php echo $addon['Active'] ? 'active' : 'inactive' ?>"
						data-id="<?php echo esc_attr( $addon['ID'] ) ?>"
						data-section="<?php echo esc_attr( $section_id ) ?>"
						>

						<?php
						$banner = '';
						if( file_exists( plugin_dir_path( $addon['File'] ) . 'assets/banner.png' ) ) {
							$banner = plugin_dir_url( $addon['File'] ) . 'assets/banner.png';
						}
						else if( file_exists( plugin_dir_path( $addon['File'] ) . 'assets/banner.svg' ) ) {
							$banner = plugin_dir_url( $addon['File'] ) . 'assets/banner.svg';
						}
						$banner = apply_filters('siteorigin_premium_addon_banner', $banner, $addon);
						?>
						<div class="so-addon-banner" data-seed="<?php echo esc_attr( substr( md5($addon['ID']), 0, 6 ) ) ?>">
							<?php if( !empty($banner) ) : ?>
								<img src="<?php echo esc_url($banner) ?>" />
							<?php endif; ?>
						</div>

						<div class="so-addon-text">

							<div class="so-addon-active-indicator"><?php _e('Active', 'so-addons-bundle') ?></div>

							<h3 class="so-addon-name"><?php echo esc_html( $addon['Name'] ); ?></h3>

							<?php
							$addon_links = apply_filters( 'siteorigin_premium_addon_action_links-' . $addon['ID'] , array() );
							if( !empty( $addon_links ) ) {
								echo '<div class="so-addon-links">';
								echo implode( ' | ', $addon_links );
								echo '</div>';
							}
							?>

							<div class="so-addon-description">
								<?php echo esc_html( $addon['Description'] ) ?>
							</div>

							<div class="so-addon-action-links">
								<?php if( !empty( $addon['CanEnable'] ) ) : ?>
									<div class="so-addon-toggle-active">
										<button class="button-secondary so-addon-activate" data-status="1"><?php esc_html_e( 'Activate', 'so-addons-bundle' ) ?></button>
										<button class="button-secondary so-addon-deactivate" data-status="0"><?php esc_html_e( 'Deactivate', 'so-addons-bundle' ) ?></button>
									</div>
								<?php endif; ?>

								<?php if( ! empty( $addon['Documentation'] ) ) : ?>
									<a href="<?php echo esc_url( $addon['Documentation'] ) ?>" target="_blank">
										<?php _e( 'Documentation', 'siteorigin-premium' ) ?>
									</a>
								<?php endif; ?>
							</div>

						</div>

					</div>
				</div>
			<?php endforeach; ?>
		<?php endforeach; ?>

		<div class="clear"></div>

	</div>

	<div class="siteorigin-logo">
		<p>
			<?php _e( 'Proudly Created By', 'siteorigin' ) ?>
		</p>
		<a href="https://siteorigin.com/" target="_blank">
			<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/siteorigin.png" />
		</a>
	</div>

</div>