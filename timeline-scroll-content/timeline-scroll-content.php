<?php
/**
 * Plugin Name:       Timeline Scroll Content
 * Description:       Custom Elementor widgets. Includes the "Process Timeline" widget: a scroll-driven timeline with a sticky detail panel.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  elementor
 * Author:      Saeed Amini
 * Author URI:  https://github.com/saeedamini144
 * Text Domain:       timeline-scroll-content
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * Elementor tested up to: 3.25.0
 *
 * @package ND_Widgets
 */

defined( 'ABSPATH' ) || exit;

define( 'ND_WIDGETS_VERSION', '1.0.0' );
define( 'ND_WIDGETS_FILE', __FILE__ );
define( 'ND_WIDGETS_PATH', plugin_dir_path( __FILE__ ) );
define( 'ND_WIDGETS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 */
final class ND_Widgets_Plugin {

	const MIN_ELEMENTOR = '3.20.0';
	const MIN_PHP       = '7.4';
	const CATEGORY      = 'nd-widgets';

	/**
	 * @var ND_Widgets_Plugin|null
	 */
	private static $instance = null;

	/**
	 * @return ND_Widgets_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	/**
	 * Run the environment checks, then hook into Elementor.
	 */
	public function init() {
		load_plugin_textdomain( 'timeline-scroll-content', false, dirname( plugin_basename( ND_WIDGETS_FILE ) ) . '/languages' );

		if ( version_compare( PHP_VERSION, self::MIN_PHP, '<' ) ) {
			add_action( 'admin_notices', [ $this, 'notice_min_php' ] );
			return;
		}

		if ( ! did_action( 'elementor/loaded' ) || ! defined( 'ELEMENTOR_VERSION' ) ) {
			add_action( 'admin_notices', [ $this, 'notice_missing_elementor' ] );
			return;
		}

		if ( version_compare( ELEMENTOR_VERSION, self::MIN_ELEMENTOR, '<' ) ) {
			add_action( 'admin_notices', [ $this, 'notice_min_elementor' ] );
			return;
		}

		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_styles' ] );
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_scripts' ] );
	}

	/**
	 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			self::CATEGORY,
			[
				'title' => esc_html__( 'ND Widgets', 'timeline-scroll-content' ),
				'icon'  => 'eicon-apps',
			]
		);
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		require_once ND_WIDGETS_PATH . 'widgets/class-timeline-scroll-content.php';
		$widgets_manager->register( new \ND_Process_Timeline_Widget() );
	}

	/**
	 * Registered only; a widget enqueues its own CSS through get_style_depends().
	 */
	public function register_styles() {
		wp_register_style(
			'timeline-scroll-content',
			ND_WIDGETS_URL . 'assets/css/timeline-scroll-content.css',
			[],
			ND_WIDGETS_VERSION
		);
	}

	/**
	 * Registered only; a widget enqueues its own JS through get_script_depends().
	 */
	public function register_scripts() {
		wp_register_script(
			'timeline-scroll-content',
			ND_WIDGETS_URL . 'assets/js/timeline-scroll-content.js',
			[],
			ND_WIDGETS_VERSION,
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);
	}

	/* ---------- Admin notices ---------- */

	public function notice_min_php() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: plugin name, 2: required PHP version */
				esc_html__( '%1$s requires PHP version %2$s or greater.', 'timeline-scroll-content' ),
				'<strong>Timeline Scroll Content</strong>',
				esc_html( self::MIN_PHP )
			)
		);
	}

	public function notice_missing_elementor() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			sprintf(
				/* translators: 1: plugin name, 2: Elementor */
				esc_html__( '%1$s requires %2$s to be installed and activated.', 'timeline-scroll-content' ),
				'<strong>Timeline Scroll Content</strong>',
				'<strong>Elementor</strong>'
			)
		);
	}

	public function notice_min_elementor() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			sprintf(
				/* translators: 1: plugin name, 2: Elementor, 3: required Elementor version */
				esc_html__( '%1$s requires %2$s version %3$s or greater.', 'timeline-scroll-content' ),
				'<strong>Timeline Scroll Content</strong>',
				'<strong>Elementor</strong>',
				esc_html( self::MIN_ELEMENTOR )
			)
		);
	}
}

ND_Widgets_Plugin::instance();
