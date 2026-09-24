<?php
/**
 * Plugin Name:       Hero Slider for Elementor
 * Description:       ویجت اسلایدر هیرو سبک و بهینه برای المنتور؛ با افکت‌های متنوع تصویر، بندانگشتی، ناوبری و تنظیمات کامل استایل.
 * Author: Saeed Amini
 * Author URI: https://github.com/saeedamini144
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  elementor
 * Text Domain:       hero-slider
 * Elementor tested up to: 3.30.0
 */

defined( 'ABSPATH' ) || exit;

define( 'HS_HERO_VERSION', '1.0.0' );
define( 'HS_HERO_FILE', __FILE__ );
define( 'HS_HERO_PATH', plugin_dir_path( __FILE__ ) );
define( 'HS_HERO_URL', plugin_dir_url( __FILE__ ) );
define( 'HS_HERO_MIN_ELEMENTOR', '3.20.0' );

final class HS_Hero_Slider_Plugin {

	public static function init() {
		add_action( 'plugins_loaded', [ __CLASS__, 'boot' ] );
	}

	public static function boot() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ __CLASS__, 'notice_missing_elementor' ] );
			return;
		}

		if ( ! defined( 'ELEMENTOR_VERSION' ) || version_compare( ELEMENTOR_VERSION, HS_HERO_MIN_ELEMENTOR, '<' ) ) {
			add_action( 'admin_notices', [ __CLASS__, 'notice_old_elementor' ] );
			return;
		}

		add_action( 'elementor/frontend/after_register_styles', [ __CLASS__, 'register_styles' ] );
		add_action( 'elementor/frontend/after_register_scripts', [ __CLASS__, 'register_scripts' ] );
		add_action( 'elementor/widgets/register', [ __CLASS__, 'register_widgets' ] );
	}

	public static function register_styles() {
		wp_register_style(
			'hs-hero-slider',
			HS_HERO_URL . 'assets/css/hero-slider.css',
			[],
			HS_HERO_VERSION
		);
	}

	public static function register_scripts() {
		wp_register_script(
			'hs-hero-slider',
			HS_HERO_URL . 'assets/js/hero-slider.js',
			[ 'elementor-frontend' ],
			HS_HERO_VERSION,
			true
		);
	}

	public static function register_widgets( $widgets_manager ) {
		require_once HS_HERO_PATH . 'includes/class-hero-slider-widget.php';
		$widgets_manager->register( new \HeroSlider\Widgets\Hero_Slider_Widget() );
	}

	public static function notice_missing_elementor() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning is-dismissible"><p>';
		echo esc_html__( 'افزونه «Hero Slider for Elementor» برای کار کردن به افزونه المنتور نیاز دارد. لطفاً المنتور را نصب و فعال کنید.', 'hero-slider' );
		echo '</p></div>';
	}

	public static function notice_old_elementor() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning is-dismissible"><p>';
		printf(
			/* translators: %s: minimum Elementor version */
			esc_html__( 'افزونه «Hero Slider for Elementor» به المنتور نسخه %s یا بالاتر نیاز دارد.', 'hero-slider' ),
			esc_html( HS_HERO_MIN_ELEMENTOR )
		);
		echo '</p></div>';
	}
}

HS_Hero_Slider_Plugin::init();
