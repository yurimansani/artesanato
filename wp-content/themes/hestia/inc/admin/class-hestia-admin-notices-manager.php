<?php
/**
 * Admin notices manager
 *
 * @package Hestia
 */

/**
 * Class Hestia_Admin_Notices_Manager
 */
class Hestia_Admin_Notices_Manager extends Hestia_Abstract_Main {
	/**
	 * Initialize notice manager.
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'translate_notice' ) );
		add_action( 'admin_init', array( $this, 'ignore_multi_language' ) );

		add_action( 'admin_init', array( $this, 'should_display_neve_notice' ) );
		$should_display_notice = get_option( 'display_neve_notice' );
		if ( $should_display_notice === 'yes' ) {
			add_action( 'admin_notices', array( $this, 'neve_notice' ) );
			add_action( 'admin_init', array( $this, 'ignore_neve' ) );
		}
	}

	/**
	 * Determine if the user activated the theme in the last 2 days.
	 */
	public function should_display_neve_notice() {
		$should_display = get_option( 'display_neve_notice' );
		if ( ! empty( $should_display ) ) {
			return;
		}

		$activated_time = get_option( 'hestia_time_activated' );
		if ( empty( $activated_time ) ) {
			return;
		}

		$current_time         = time();
		$days_from_activation = intval( ( $current_time - $activated_time ) / 86400 );
		update_option( 'display_neve_notice', 'no' );
		if ( $days_from_activation < 2 ) {
			update_option( 'display_neve_notice', 'yes' );
		}
	}

	/**
	 * Add notice for front page translations.
	 */
	public function translate_notice() {
		global $current_user;
		$user_id = $current_user->ID;

		/* Check that the user hasn't already clicked to ignore the message */
		if ( get_user_meta( $user_id, 'hestia_ignore_multi_language_upsell_notice' ) ) {
			return;
		}
		if ( ! $this->should_display_translate_notice() ) {
			return;
		}

		echo '<div class="notice notice-warning" style="position:relative;">';
		printf( '<a href="%s" class="notice-dismiss" style="text-decoration:none;"></a>', '?hestia_nag_ignore=0' );
		echo '<p>';
		/* translators: Upsell to get the pro version */
		printf( esc_html__( 'Hestia front-page is not multi-language compatible, for this feature %s.', 'hestia' ), sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( apply_filters( 'hestia_upgrade_link_from_child_theme_filter', 'https://themeisle.com/themes/hestia-pro/upgrade/' ) ), esc_html__( 'Get the PRO version!', 'hestia' ) ) );
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Check if Polylang, TranslatePress or WPML are installed
	 * and the custom frontpage is selected
	 *
	 * @return bool
	 */
	private function should_display_translate_notice() {
		if ( defined( 'HESTIA_PRO_FLAG' ) ) {
			return false;
		}

		if ( get_option( 'show_on_front' ) === 'page' ) {
			if ( defined( 'POLYLANG_VERSION' ) ) {
				return true;
			}
			if ( defined( 'TRP_PLUGIN_VERSION' ) ) {
				return true;
			}
			if ( get_option( 'icl_sitepress_settings' ) !== false ) {
				return true;
			}

			return false;
		}
	}

	/**
	 * Ignore notice.
	 */
	public function ignore_multi_language() {
		global $current_user;
		$user_id = $current_user->ID;
		/* If user clicks to ignore the notice, add that to their user meta */
		if ( isset( $_GET['hestia_nag_ignore'] ) && '0' == $_GET['hestia_nag_ignore'] ) {
			add_user_meta( $user_id, 'hestia_ignore_multi_language_upsell_notice', 'true', true );
		}
	}

	/**
	 * Add a dismissible notice in the dashboard about Neve
	 */
	public function neve_notice() {
		global $current_user;
		$user_id        = $current_user->ID;
		$ignored_notice = get_user_meta( $user_id, 'ignore_neve_notice' );
		if ( ! empty( $ignored_notice ) ) {
			return;
		}
		$dismiss_button =
			sprintf(
				'<a href="%s" class="notice-dismiss" style="text-decoration:none;"></a>',
				'?nag_ignore_neve=0'
			);
		$message        =
			sprintf(
				/* translators: Install Neve link */
				esc_html__( 'Check out %1$s. Fully AMP optimized and responsive, Neve will load in mere seconds and adapt perfectly on any viewing device. Neve works perfectly with Gutenberg and the most popular page builders. You will love it!', 'hestia' ),
				sprintf(
					/* translators: Install Neve link */
					'<a target="_blank" href="%1$s"><strong>%2$s</strong></a>',
					esc_url( admin_url( 'theme-install.php?theme=neve' ) ),
					esc_html__( 'our newest theme', 'hestia' )
				)
			);
		printf(
			'<div class="notice updated" style="position:relative;">%1$s<p>%2$s</p></div>',
			$dismiss_button,
			$message
		);
	}

	/**
	 * Update the ignore_neve_notice option to true, to dismiss the notice from the dashboard
	 */
	public function ignore_neve() {
		global $current_user;
		$user_id = $current_user->ID;
		/* If user clicks to ignore the notice, add that to their user meta */
		if ( isset( $_GET['nag_ignore_neve'] ) && '0' == $_GET['nag_ignore_neve'] ) {
			add_user_meta( $user_id, 'ignore_neve_notice', 'true', true );
		}
	}

}
