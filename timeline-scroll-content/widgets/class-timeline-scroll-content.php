<?php
/**
 * Process Timeline widget.
 *
 * A scroll-driven timeline (right) with a sticky detail panel (left).
 * On tablet / mobile the details move inside each step.
 *
 * @package ND_Widgets
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Control_Media;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

class ND_Process_Timeline_Widget extends Widget_Base {

	/* ---------------------------------------------------------------------
	 * Identity
	 * ------------------------------------------------------------------ */

	public function get_name() {
		return 'nd_process_timeline';
	}

	public function get_title() {
		return esc_html__( 'Process Timeline', 'timeline-scroll-content' );
	}

	public function get_icon() {
		return 'eicon-time-line';
	}

	public function get_categories() {
		return [ 'nd-widgets' ];
	}

	public function get_keywords() {
		return [ 'process', 'timeline', 'steps', 'scroll', 'sticky', 'nd' ];
	}

	public function get_style_depends() {
		return [ 'timeline-scroll-content' ];
	}

	public function get_script_depends() {
		return [ 'timeline-scroll-content' ];
	}

	/**
	 * Optimized DOM: no extra .elementor-widget-container wrapper.
	 */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * The output depends on nothing request-specific, so the element cache may be used.
	 *
	 * Do not read the element settings here: Elementor also calls this method on the widget
	 * type instance, which has no settings. Elementor itself checks the saved settings
	 * (including repeater items) for dynamic tags and skips the cache when it finds one.
	 */
	protected function is_dynamic_content(): bool {
		return false;
	}

	/* ---------------------------------------------------------------------
	 * Small helpers
	 * ------------------------------------------------------------------ */

	private static function size( $size, $unit = 'px' ) {
		return [
			'unit' => $unit,
			'size' => $size,
		];
	}

	private static function dims( $top, $right, $bottom, $left, $unit = 'px' ) {
		return [
			'unit'     => $unit,
			'top'      => (string) $top,
			'right'    => (string) $right,
			'bottom'   => (string) $bottom,
			'left'     => (string) $left,
			'isLinked' => false,
		];
	}

	/**
	 * Default steps = the real content of the design.
	 *
	 * @return array
	 */
	private function get_default_steps() {
		$rows = [
			[
				'discovery',
				'Discovery & positioning',
				'We learn your business, your buyers, and your competitors, then define the one position your brand should own in a crowded market.',
				'Phase 01 · Research',
				'Finding the position only you can own',
				'Stakeholder interviews, customer conversations and a competitive audit show where the market is crowded and where it is empty. The result is a positioning statement the whole identity is built on.',
				3,
			],
			[
				'naming',
				'Naming & messaging',
				'If a name is needed, we develop and test options. Either way, we set the messaging pillars and tone of voice your brand will speak in everywhere.',
				'Phase 02 · Language',
				'A name and a voice people remember',
				'We generate and test name candidates, then define messaging pillars, taglines and a tone-of-voice guide so every sentence sounds like the same brand.',
				2,
			],
			[
				'logo',
				'Logo exploration',
				'Several distinct directions for mark and logotype, sketched and tested against your positioning — not one idea refined too early.',
				'Phase 03 · Concept',
				'Wide before narrow',
				'We sketch several unrelated directions and test each against the positioning, so the final choice is a decision between real options, not a default.',
				3,
			],
			[
				'refine',
				'Logotype refinement',
				'The chosen direction is refined and stress-tested: small sizes, single color, dark and light backgrounds, favicon and app-icon crops.',
				'Phase 04 · Craft',
				'Refined until it holds up everywhere',
				'Spacing, proportions and details are tuned, then the mark is pushed through real conditions: 16px favicons, one-color print, reversed on dark.',
				2,
			],
			[
				'system',
				'Color & type system',
				'A palette and type scale built to work across print, screen, and languages — not just the three colors that looked good in a mockup.',
				'Phase 05 · System',
				'Color and type that scale',
				'Primary and supporting palettes with accessibility ratios, plus a type scale and font pairing tested across print, web and multiple scripts.',
				3,
			],
			[
				'guide',
				'Guidelines',
				'A brand book showing correct and incorrect usage, spacing, co-branding rules, and real application examples — so anyone can apply the identity consistently.',
				'Phase 06 · Documentation',
				'A brand book people actually use',
				'Clear do and don’t pages, clear-space rules, co-branding and real examples, so a new designer or vendor can apply the identity correctly without calling us.',
				2,
			],
			[
				'launch',
				'Application & handover',
				'The identity applied to real touchpoints — business cards, packaging, social templates, signage — then handed over with source files and a walkthrough for your team.',
				'Phase 07 · Launch',
				'From files to the real world',
				'We apply the identity to business cards, packaging, social templates and signage, then hand over organised source files and walk your team through them.',
				3,
			],
		];

		$items = [];

		foreach ( $rows as $row ) {
			list( $seed, $title, $text, $subtitle, $heading, $body, $count ) = $row;

			$item = [
				'step_title'      => $title,
				'step_text'       => $text,
				'detail_subtitle' => $subtitle,
				'detail_heading'  => $heading,
				'detail_body'     => $body,
				'images_alt'      => '',
			];

			for ( $n = 1; $n <= 3; $n++ ) {
				$item[ 'image_' . $n ] = [
					'url' => $n <= $count
						? sprintf( 'https://picsum.photos/seed/nd-%1$s-%2$d/%3$s', $seed, $n, 1 === $n ? '1200/675' : '800/500' )
						: '',
				];
			}

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Typography + (optional) colour + text-shadow + alignment + margin for one text element.
	 * Must be called inside an open section / tab.
	 *
	 * @param string $name     Control id prefix.
	 * @param string $selector Selector, relative to {{WRAPPER}}.
	 * @param array  $o        typography (fields_options), color (default, or null for none), margin (array of 4 responsive defaults).
	 */
	private function add_text_controls( $name, $selector, array $o ) {
		$selector = '{{WRAPPER}} ' . $selector;

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'           => $name . '_typography',
				'selector'       => $selector,
				'fields_options' => array_merge(
					[ 'typography' => [ 'default' => 'custom' ] ],
					isset( $o['typography'] ) ? $o['typography'] : []
				),
			]
		);

		if ( isset( $o['color'] ) ) {
			$this->add_control(
				$name . '_color',
				[
					'label'     => esc_html__( 'Color', 'timeline-scroll-content' ),
					'type'      => Controls_Manager::COLOR,
					'default'   => $o['color'],
					'global'    => [ 'active' => true ],
					'selectors' => [
						$selector => 'color: {{VALUE}};',
					],
				]
			);
		}

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => $name . '_text_shadow',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			$name . '_align',
			[
				'label'     => esc_html__( 'Alignment', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'start'  => [
						'title' => esc_html__( 'Start', 'timeline-scroll-content' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'timeline-scroll-content' ),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__( 'End', 'timeline-scroll-content' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'classes'   => 'elementor-control-start-end',
				'selectors' => [
					$selector => 'text-align: {{VALUE}};',
				],
			]
		);

		if ( isset( $o['margin'] ) ) {
			$margin = $o['margin'];
			$this->add_responsive_control(
				$name . '_margin',
				[
					'label'          => esc_html__( 'Margin', 'timeline-scroll-content' ),
					'type'           => Controls_Manager::DIMENSIONS,
					'size_units'     => [ 'px', 'em', 'rem' ],
					'default'        => $margin['default'],
					'tablet_default' => isset( $margin['tablet'] ) ? $margin['tablet'] : $margin['default'],
					'mobile_default' => isset( $margin['mobile'] ) ? $margin['mobile'] : ( isset( $margin['tablet'] ) ? $margin['tablet'] : $margin['default'] ),
					'selectors'      => [
						$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);
		}
	}

	/* ---------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {
		$this->register_content_steps();
		$this->register_content_timeline();
		$this->register_content_detail();
		$this->register_content_behavior();

		$this->register_style_layout();
		$this->register_style_rail();
		$this->register_style_node();
		$this->register_style_step_title();
		$this->register_style_step_text();
		$this->register_style_detail_box();
		$this->register_style_eyebrow();
		$this->register_style_detail_heading();
		$this->register_style_detail_body();
		$this->register_style_images();
		$this->register_style_animation();
	}

	/* ----- Content tab ----- */

	private function register_content_steps() {
		$this->start_controls_section(
			'section_steps',
			[
				'label' => esc_html__( 'Steps', 'timeline-scroll-content' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'step_title',
			[
				'label'       => esc_html__( 'Step title', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Step title', 'timeline-scroll-content' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$repeater->add_control(
			'step_text',
			[
				'label'       => esc_html__( 'Step text', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$repeater->add_control(
			'heading_detail',
			[
				'label'     => esc_html__( 'Detail panel', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$repeater->add_control(
			'detail_subtitle',
			[
				'label'       => esc_html__( 'Subtitle', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$repeater->add_control(
			'detail_heading',
			[
				'label'       => esc_html__( 'Heading', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$repeater->add_control(
			'detail_body',
			[
				'label'       => esc_html__( 'Body text', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 5,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$repeater->add_control(
			'heading_images',
			[
				'label'     => esc_html__( 'Images (first one is shown large)', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		for ( $n = 1; $n <= 3; $n++ ) {
			$repeater->add_control(
				'image_' . $n,
				[
					/* translators: %d: image number */
					'label'   => sprintf( esc_html__( 'Image %d', 'timeline-scroll-content' ), $n ),
					'type'    => Controls_Manager::MEDIA,
					'default' => [ 'url' => '' ],
					'dynamic' => [ 'active' => true ],
				]
			);
		}

		$repeater->add_control(
			'images_alt',
			[
				'label'       => esc_html__( 'Fallback alt text', 'timeline-scroll-content' ),
				'description' => esc_html__( 'Used when the image has no alt text in the media library. If empty, the detail heading is used.', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'steps',
			[
				'label'       => esc_html__( 'Steps', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => $this->get_default_steps(),
				'title_field' => '{{{ step_title }}}',
			]
		);

		$this->end_controls_section();
	}

	private function register_content_timeline() {
		$this->start_controls_section(
			'section_timeline',
			[
				'label' => esc_html__( 'Timeline', 'timeline-scroll-content' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'aria_label',
			[
				'label'       => esc_html__( 'Accessible label', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Process steps', 'timeline-scroll-content' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'step_title_tag',
			[
				'label'   => esc_html__( 'Step title HTML tag', 'timeline-scroll-content' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_tag_options(),
				'default' => 'h3',
			]
		);

		$this->add_control(
			'show_step_text',
			[
				'label'        => esc_html__( 'Show step text', 'timeline-scroll-content' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'timeline-scroll-content' ),
				'label_off'    => esc_html__( 'Hide', 'timeline-scroll-content' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();
	}

	private function register_content_detail() {
		$this->start_controls_section(
			'section_detail',
			[
				'label' => esc_html__( 'Detail Panel', 'timeline-scroll-content' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_detail',
			[
				'label'        => esc_html__( 'Show detail panel', 'timeline-scroll-content' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'timeline-scroll-content' ),
				'label_off'    => esc_html__( 'Hide', 'timeline-scroll-content' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$switchers = [
			'show_eyebrow'        => esc_html__( 'Show subtitle', 'timeline-scroll-content' ),
			'show_detail_heading' => esc_html__( 'Show heading', 'timeline-scroll-content' ),
			'show_detail_body'    => esc_html__( 'Show body text', 'timeline-scroll-content' ),
			'show_images'         => esc_html__( 'Show images', 'timeline-scroll-content' ),
		];

		foreach ( $switchers as $id => $label ) {
			$this->add_control(
				$id,
				[
					'label'        => $label,
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Show', 'timeline-scroll-content' ),
					'label_off'    => esc_html__( 'Hide', 'timeline-scroll-content' ),
					'return_value' => 'yes',
					'default'      => 'yes',
					'condition'    => [ 'show_detail' => 'yes' ],
				]
			);
		}

		$this->add_control(
			'detail_heading_tag',
			[
				'label'     => esc_html__( 'Heading HTML tag', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => $this->get_tag_options(),
				'default'   => 'h4',
				'condition' => [
					'show_detail'         => 'yes',
					'show_detail_heading' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'thumbnail',
				'default'   => 'large',
				'separator' => 'before',
				'condition' => [
					'show_detail' => 'yes',
					'show_images' => 'yes',
				],
			]
		);

		$this->add_control(
			'image_loading',
			[
				'label'       => esc_html__( 'First image loading', 'timeline-scroll-content' ),
				'description' => esc_html__( 'Use "Eager" when the widget is at the top of the page (LCP image). All other images are always lazy-loaded.', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'lazy'  => esc_html__( 'Lazy', 'timeline-scroll-content' ),
					'eager' => esc_html__( 'Eager + high fetch priority', 'timeline-scroll-content' ),
				],
				'default'     => 'lazy',
				'condition'   => [
					'show_detail' => 'yes',
					'show_images' => 'yes',
				],
			]
		);

		$this->add_control(
			'hide_sub_short',
			[
				'label'        => esc_html__( 'Hide small images on short screens', 'timeline-scroll-content' ),
				'description'  => esc_html__( 'On desktop windows shorter than 700px, only the large image stays in the sticky panel.', 'timeline-scroll-content' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'prefix_class' => 'nd-pt-short-hide-',
				'condition'    => [
					'show_detail' => 'yes',
					'show_images' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_content_behavior() {
		$this->start_controls_section(
			'section_behavior',
			[
				'label' => esc_html__( 'Scroll Behavior', 'timeline-scroll-content' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'trigger_line',
			[
				'label'       => esc_html__( 'Trigger line', 'timeline-scroll-content' ),
				'description' => esc_html__( 'A step becomes active once its node passes this point of the screen height (0% = top, 100% = bottom).', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ '%' ],
				'range'       => [
					'%' => [
						'min' => 10,
						'max' => 90,
					],
				],
				'default'     => self::size( 50, '%' ),
			]
		);

		$this->end_controls_section();
	}

	/* ----- Style tab ----- */

	private function register_style_layout() {
		$this->start_controls_section(
			'style_layout',
			[
				'label' => esc_html__( 'Layout / Box', 'timeline-scroll-content' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'layout_mode',
			[
				'label'              => esc_html__( 'Layout', 'timeline-scroll-content' ),
				'type'               => Controls_Manager::SELECT,
				'options'            => [
					'split'   => esc_html__( 'Sticky panel + timeline', 'timeline-scroll-content' ),
					'stacked' => esc_html__( 'Stacked (details inside steps)', 'timeline-scroll-content' ),
				],
				'default'            => 'split',
				'tablet_default'     => 'stacked',
				'mobile_default'     => 'stacked',
				// Each option sets several custom properties at once.
				'selectors_dictionary' => [
					'split'   => 'minmax(0, 1fr) minmax(0, 1fr); --nd-pt-panel-display: grid; --nd-pt-inline-display: none',
					'stacked' => 'minmax(0, 1fr); --nd-pt-panel-display: none; --nd-pt-inline-display: block',
				],
				'selectors'          => [
					'{{WRAPPER}} .nd-pt' => '--nd-pt-cols: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'panel_position',
			[
				'label'                => esc_html__( 'Panel position', 'timeline-scroll-content' ),
				'type'                 => Controls_Manager::SELECT,
				'options'              => [
					'start' => esc_html__( 'Start (left in LTR)', 'timeline-scroll-content' ),
					'end'   => esc_html__( 'End (right in LTR)', 'timeline-scroll-content' ),
				],
				'default'              => 'start',
				'selectors_dictionary' => [
					'start' => '0',
					'end'   => '1',
				],
				'selectors'            => [
					'{{WRAPPER}} .nd-pt__panel' => 'order: {{VALUE}};',
				],
				'condition'            => [ 'show_detail' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'layout_max_width',
			[
				'label'          => esc_html__( 'Content width', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', '%' ],
				'range'          => [
					'px' => [
						'min' => 280,
						'max' => 1800,
					],
					'%'  => [
						'min' => 20,
						'max' => 100,
					],
				],
				'default'        => self::size( 1120 ),
				'tablet_default' => self::size( 720 ),
				'selectors'      => [
					'{{WRAPPER}} .nd-pt__layout' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'layout_gap',
			[
				'label'          => esc_html__( 'Gap between panel and timeline', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', 'em', 'rem' ],
				'range'          => [
					'px' => [
						'min' => 0,
						'max' => 240,
					],
				],
				'default'        => self::size( 80 ),
				'tablet_default' => self::size( 32 ),
				'selectors'      => [
					'{{WRAPPER}} .nd-pt__layout' => 'column-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'layout_padding_x',
			[
				'label'          => esc_html__( 'Side padding', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', 'em', 'rem' ],
				'range'          => [
					'px' => [
						'min' => 0,
						'max' => 120,
					],
				],
				'default'        => self::size( 32 ),
				'tablet_default' => self::size( 32 ),
				'mobile_default' => self::size( 16 ),
				'selectors'      => [
					'{{WRAPPER}} .nd-pt__layout' => 'padding-inline: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'panel_sticky_top',
			[
				'label'      => esc_html__( 'Panel sticky offset (top)', 'timeline-scroll-content' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 400,
					],
					'vh' => [
						'min' => 0,
						'max' => 60,
					],
				],
				'default'    => self::size( 12, 'vh' ),
				'selectors'  => [
					'{{WRAPPER}} .nd-pt__panel' => 'top: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'show_detail' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'steps_spacing',
			[
				'label'          => esc_html__( 'Space between steps', 'timeline-scroll-content' ),
				'description'    => esc_html__( 'Also the scroll distance each step gets.', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', 'vh' ],
				'range'          => [
					'px' => [
						'min' => 0,
						'max' => 600,
					],
					'vh' => [
						'min' => 0,
						'max' => 80,
					],
				],
				'default'        => self::size( 24, 'vh' ),
				'tablet_default' => self::size( 72 ),
				'mobile_default' => self::size( 44 ),
				'selectors'      => [
					'{{WRAPPER}} .nd-pt__step:not(:last-child)' => 'padding-block-end: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'timeline_bottom_space',
			[
				'label'          => esc_html__( 'Space after the last step', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', 'vh' ],
				'range'          => [
					'px' => [
						'min' => 0,
						'max' => 800,
					],
					'vh' => [
						'min' => 0,
						'max' => 80,
					],
				],
				'default'        => self::size( 42, 'vh' ),
				'tablet_default' => self::size( 8, 'vh' ),
				'mobile_default' => self::size( 8, 'vh' ),
				'selectors'      => [
					'{{WRAPPER}} .nd-pt__timeline' => 'padding-block-end: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'step_gap',
			[
				'label'          => esc_html__( 'Gap between node and content', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', 'em', 'rem' ],
				'range'          => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'        => self::size( 26 ),
				'tablet_default' => self::size( 16 ),
				'mobile_default' => self::size( 12 ),
				'selectors'      => [
					'{{WRAPPER}} .nd-pt__step' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'inline_spacing',
			[
				'label'      => esc_html__( 'Inline detail top spacing (stacked layout)', 'timeline-scroll-content' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'    => self::size( 20 ),
				'selectors'  => [
					'{{WRAPPER}} .nd-pt__inline' => 'margin-block-start: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'show_detail' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_style_rail() {
		$this->start_controls_section(
			'style_rail',
			[
				'label' => esc_html__( 'Rail & Progress', 'timeline-scroll-content' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'rail_width',
			[
				'label'      => esc_html__( 'Rail width', 'timeline-scroll-content' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 12,
					],
				],
				'default'    => self::size( 2 ),
				'selectors'  => [
					'{{WRAPPER}} .nd-pt' => '--nd-pt-rail-w: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'rail_color',
			[
				'label'     => esc_html__( 'Rail color', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(29, 34, 36, 0.14)',
				'global'    => [ 'active' => true ],
				'selectors' => [
					'{{WRAPPER}} .nd-pt__rail' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'rail_fill_color',
			[
				'label'     => esc_html__( 'Progress color', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FA3000',
				'global'    => [ 'active' => true ],
				'selectors' => [
					'{{WRAPPER}} .nd-pt__rail-fill' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_style_node() {
		$node = '{{WRAPPER}} .nd-pt__node';

		$this->start_controls_section(
			'style_node',
			[
				'label' => esc_html__( 'Step Node', 'timeline-scroll-content' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'node_size',
			[
				'label'          => esc_html__( 'Size', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px' ],
				'range'          => [
					'px' => [
						'min' => 16,
						'max' => 120,
					],
				],
				'default'        => self::size( 40 ),
				'tablet_default' => self::size( 32 ),
				'mobile_default' => self::size( 32 ),
				'selectors'      => [
					'{{WRAPPER}} .nd-pt' => '--nd-pt-node: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'           => 'node_typography',
				'selector'       => $node,
				'fields_options' => [
					'typography' => [ 'default' => 'custom' ],
					'font_size'  => [
						'default'        => self::size( 0.9, 'rem' ),
						'tablet_default' => self::size( 0.78, 'rem' ),
						'mobile_default' => self::size( 0.78, 'rem' ),
					],
					'font_weight' => [ 'default' => '700' ],
				],
			]
		);

		$this->add_responsive_control(
			'node_radius',
			[
				'label'      => esc_html__( 'Border radius', 'timeline-scroll-content' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px' ],
				'range'      => [
					'%'  => [
						'min' => 0,
						'max' => 50,
					],
					'px' => [
						'min' => 0,
						'max' => 60,
					],
				],
				'default'    => self::size( 50, '%' ),
				'selectors'  => [
					$node => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'node_tabs' );

		/* Normal */
		$this->start_controls_tab(
			'node_tab_normal',
			[ 'label' => esc_html__( 'Normal', 'timeline-scroll-content' ) ]
		);

		$this->add_control(
			'node_color',
			[
				'label'     => esc_html__( 'Text color', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#8B9296',
				'global'    => [ 'active' => true ],
				'selectors' => [
					$node => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'node_background',
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => $node,
				'fields_options' => [
					'background' => [ 'default' => 'classic' ],
					'color'      => [ 'default' => '#FFFFFF' ],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'node_border',
				'selector'       => $node,
				'fields_options' => [
					'border' => [ 'default' => 'solid' ],
					'width'  => [
						'default' => [
							'top'      => '1',
							'right'    => '1',
							'bottom'   => '1',
							'left'     => '1',
							'isLinked' => true,
						],
					],
					'color'  => [ 'default' => 'rgba(29, 34, 36, 0.18)' ],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'node_box_shadow',
				'selector' => $node,
			]
		);

		$this->end_controls_tab();

		/* Active */
		$this->start_controls_tab(
			'node_tab_active',
			[ 'label' => esc_html__( 'Active', 'timeline-scroll-content' ) ]
		);

		$active = '{{WRAPPER}} .nd-pt__step.is-active .nd-pt__node';

		$this->add_control(
			'node_color_active',
			[
				'label'     => esc_html__( 'Text color', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#F7F5F1',
				'global'    => [ 'active' => true ],
				'selectors' => [
					$active => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'node_background_active',
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => $active,
				'fields_options' => [
					'background' => [ 'default' => 'classic' ],
					'color'      => [ 'default' => '#FA3000' ],
				],
			]
		);

		$this->add_control(
			'node_border_color_active',
			[
				'label'     => esc_html__( 'Border color', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FA3000',
				'global'    => [ 'active' => true ],
				'selectors' => [
					$active => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'           => 'node_box_shadow_active',
				'selector'       => $active,
				'fields_options' => [
					'box_shadow_type' => [ 'default' => 'yes' ],
					'box_shadow'      => [
						'default' => [
							'horizontal' => 0,
							'vertical'   => 0,
							'blur'       => 0,
							'spread'     => 4,
							'color'      => 'rgba(250, 48, 0, 0.18)',
						],
					],
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	private function register_style_step_title() {
		$sel = '{{WRAPPER}} .nd-pt__step-title';

		$this->start_controls_section(
			'style_step_title',
			[
				'label' => esc_html__( 'Step Title', 'timeline-scroll-content' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_text_controls(
			'step_title',
			'.nd-pt__step-title',
			[
				'typography' => [
					'font_size'   => [
						'default'        => self::size( 1.45, 'rem' ),
						'tablet_default' => self::size( 1.3, 'rem' ),
						'mobile_default' => self::size( 1.2, 'rem' ),
					],
					'font_weight' => [ 'default' => '500' ],
					'line_height' => [ 'default' => self::size( 1.3, 'em' ) ],
				],
				'margin'     => [
					'default' => self::dims( 0, 0, 10, 0 ),
				],
			]
		);

		$this->start_controls_tabs( 'step_title_tabs' );

		$this->start_controls_tab(
			'step_title_tab_normal',
			[ 'label' => esc_html__( 'Normal', 'timeline-scroll-content' ) ]
		);

		$this->add_control(
			'step_title_color',
			[
				'label'     => esc_html__( 'Color', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1D2224',
				'global'    => [ 'active' => true ],
				'selectors' => [
					$sel => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'step_title_opacity',
			[
				'label'     => esc_html__( 'Opacity', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					],
				],
				'default'   => self::size( 0.4 ),
				'selectors' => [
					$sel => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'step_title_tab_active',
			[ 'label' => esc_html__( 'Active', 'timeline-scroll-content' ) ]
		);

		$this->add_control(
			'step_title_color_active',
			[
				'label'     => esc_html__( 'Color', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1D2224',
				'global'    => [ 'active' => true ],
				'selectors' => [
					'{{WRAPPER}} .nd-pt__step.is-active .nd-pt__step-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'step_title_opacity_active',
			[
				'label'     => esc_html__( 'Opacity', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					],
				],
				'default'   => self::size( 1 ),
				'selectors' => [
					'{{WRAPPER}} .nd-pt__step.is-active .nd-pt__step-title' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	private function register_style_step_text() {
		$sel = '{{WRAPPER}} .nd-pt__text';

		$this->start_controls_section(
			'style_step_text',
			[
				'label'     => esc_html__( 'Step Text', 'timeline-scroll-content' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_step_text' => 'yes' ],
			]
		);

		$this->add_text_controls(
			'step_text',
			'.nd-pt__text',
			[
				'typography' => [
					'font_size'   => [
						'default'        => self::size( 0.96, 'rem' ),
						'mobile_default' => self::size( 0.92, 'rem' ),
					],
					'line_height' => [ 'default' => self::size( 1.65, 'em' ) ],
				],
				'margin'     => [
					'default' => self::dims( 0, 0, 0, 0 ),
				],
			]
		);

		$this->add_responsive_control(
			'step_text_max_width',
			[
				'label'          => esc_html__( 'Max width', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', '%' ],
				'range'          => [
					'px' => [
						'min' => 100,
						'max' => 1000,
					],
					'%'  => [
						'min' => 10,
						'max' => 100,
					],
				],
				'default'        => self::size( 460 ),
				'tablet_default' => self::size( 100, '%' ),
				'selectors'      => [
					$sel => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'step_text_tabs' );

		$this->start_controls_tab(
			'step_text_tab_normal',
			[ 'label' => esc_html__( 'Normal', 'timeline-scroll-content' ) ]
		);

		$this->add_control(
			'step_text_color',
			[
				'label'     => esc_html__( 'Color', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#8B9296',
				'global'    => [ 'active' => true ],
				'selectors' => [
					$sel => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'step_text_opacity',
			[
				'label'     => esc_html__( 'Opacity', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					],
				],
				'default'   => self::size( 0.7 ),
				'selectors' => [
					$sel => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'step_text_tab_active',
			[ 'label' => esc_html__( 'Active', 'timeline-scroll-content' ) ]
		);

		$this->add_control(
			'step_text_color_active',
			[
				'label'     => esc_html__( 'Color', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#5B605F',
				'global'    => [ 'active' => true ],
				'selectors' => [
					'{{WRAPPER}} .nd-pt__step.is-active .nd-pt__text' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'step_text_opacity_active',
			[
				'label'     => esc_html__( 'Opacity', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					],
				],
				'default'   => self::size( 1 ),
				'selectors' => [
					'{{WRAPPER}} .nd-pt__step.is-active .nd-pt__text' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	private function register_style_detail_box() {
		$sel = '{{WRAPPER}} .nd-pt__detail, {{WRAPPER}} .nd-pt__inline';

		$this->start_controls_section(
			'style_detail_box',
			[
				'label'     => esc_html__( 'Detail Panel Box', 'timeline-scroll-content' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_detail' => 'yes' ],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'detail_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => $sel,
			]
		);

		$this->add_responsive_control(
			'detail_padding',
			[
				'label'      => esc_html__( 'Padding', 'timeline-scroll-content' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'detail_border',
				'selector' => $sel,
			]
		);

		$this->add_responsive_control(
			'detail_radius',
			[
				'label'      => esc_html__( 'Border radius', 'timeline-scroll-content' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'detail_box_shadow',
				'selector' => $sel,
			]
		);

		$this->end_controls_section();
	}

	private function register_style_eyebrow() {
		$this->start_controls_section(
			'style_eyebrow',
			[
				'label'     => esc_html__( 'Detail Subtitle', 'timeline-scroll-content' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_detail'  => 'yes',
					'show_eyebrow' => 'yes',
				],
			]
		);

		$this->add_text_controls(
			'eyebrow',
			'.nd-pt__eyebrow',
			[
				'typography' => [
					'font_size'      => [ 'default' => self::size( 0.78, 'rem' ) ],
					'font_weight'    => [ 'default' => '700' ],
					'text_transform' => [ 'default' => 'uppercase' ],
					'letter_spacing' => [ 'default' => self::size( 0.14, 'em' ) ],
				],
				'color'      => '#FA3000',
				'margin'     => [
					'default' => self::dims( 0, 0, 10, 0 ),
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_style_detail_heading() {
		$this->start_controls_section(
			'style_detail_heading',
			[
				'label'     => esc_html__( 'Detail Heading', 'timeline-scroll-content' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_detail'         => 'yes',
					'show_detail_heading' => 'yes',
				],
			]
		);

		$this->add_text_controls(
			'detail_heading',
			'.nd-pt__heading',
			[
				'typography' => [
					'font_size'      => [
						'default'        => self::size( 2, 'rem' ),
						'tablet_default' => self::size( 1.35, 'rem' ),
						'mobile_default' => self::size( 1.15, 'rem' ),
					],
					'font_weight'    => [ 'default' => '500' ],
					'line_height'    => [ 'default' => self::size( 1.2, 'em' ) ],
					'letter_spacing' => [ 'default' => self::size( -0.01, 'em' ) ],
				],
				'color'      => '#1D2224',
				'margin'     => [
					'default' => self::dims( 0, 0, 14, 0 ),
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_style_detail_body() {
		$this->start_controls_section(
			'style_detail_body',
			[
				'label'     => esc_html__( 'Detail Body', 'timeline-scroll-content' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_detail'      => 'yes',
					'show_detail_body' => 'yes',
				],
			]
		);

		$this->add_text_controls(
			'detail_body',
			'.nd-pt__body',
			[
				'typography' => [
					'font_size'   => [
						'default'        => self::size( 1, 'rem' ),
						'tablet_default' => self::size( 0.95, 'rem' ),
						'mobile_default' => self::size( 0.92, 'rem' ),
					],
					'line_height' => [ 'default' => self::size( 1.7, 'em' ) ],
				],
				'color'      => '#5B605F',
				'margin'     => [
					'default' => self::dims( 0, 0, 24, 0 ),
					'tablet'  => self::dims( 0, 0, 16, 0 ),
				],
			]
		);

		$this->add_responsive_control(
			'detail_body_max_width',
			[
				'label'          => esc_html__( 'Max width', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', '%' ],
				'range'          => [
					'px' => [
						'min' => 100,
						'max' => 1000,
					],
					'%'  => [
						'min' => 10,
						'max' => 100,
					],
				],
				'default'        => self::size( 520 ),
				'tablet_default' => self::size( 100, '%' ),
				'selectors'      => [
					'{{WRAPPER}} .nd-pt__body' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_style_images() {
		$img = '{{WRAPPER}} .nd-pt__img';

		$aspects = [
			'auto'    => esc_html__( 'Auto (original)', 'timeline-scroll-content' ),
			'1 / 1'   => '1:1',
			'4 / 3'   => '4:3',
			'3 / 2'   => '3:2',
			'16 / 10' => '16:10',
			'16 / 9'  => '16:9',
			'21 / 9'  => '21:9',
			'16 / 7'  => '16:7',
		];

		$this->start_controls_section(
			'style_images',
			[
				'label'     => esc_html__( 'Detail Images', 'timeline-scroll-content' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_detail' => 'yes',
					'show_images' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'images_gap',
			[
				'label'          => esc_html__( 'Gap', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', 'em', 'rem' ],
				'range'          => [
					'px' => [
						'min' => 0,
						'max' => 60,
					],
				],
				'default'        => self::size( 12 ),
				'mobile_default' => self::size( 8 ),
				'selectors'      => [
					'{{WRAPPER}} .nd-pt__media' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'images_aspect_main',
			[
				'label'     => esc_html__( 'Large image ratio', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => $aspects,
				'default'   => '16 / 9',
				'selectors' => [
					'{{WRAPPER}} .nd-pt__img--main' => 'aspect-ratio: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'images_aspect_sub',
			[
				'label'          => esc_html__( 'Small images ratio', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SELECT,
				'options'        => $aspects,
				'default'        => '16 / 10',
				'tablet_default' => '4 / 3',
				'selectors'      => [
					'{{WRAPPER}} .nd-pt__img--sub' => 'aspect-ratio: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'images_aspect_sub_single',
			[
				'label'     => esc_html__( 'Single small image ratio', 'timeline-scroll-content' ),
				'description' => esc_html__( 'Used when a step has only two images.', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => $aspects,
				'default'   => '16 / 7',
				'selectors' => [
					'{{WRAPPER}} .nd-pt__media--2 .nd-pt__img--sub' => 'aspect-ratio: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'images_max_height_main',
			[
				'label'       => esc_html__( 'Large image max height (sticky panel)', 'timeline-scroll-content' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px', 'vh' ],
				'range'       => [
					'px' => [
						'min' => 80,
						'max' => 900,
					],
					'vh' => [
						'min' => 10,
						'max' => 100,
					],
				],
				'default'     => self::size( 34, 'vh' ),
				'selectors'   => [
					'{{WRAPPER}} .nd-pt__panel .nd-pt__img--main' => 'max-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'images_max_height_sub',
			[
				'label'      => esc_html__( 'Small images max height (sticky panel)', 'timeline-scroll-content' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [
						'min' => 60,
						'max' => 600,
					],
					'vh' => [
						'min' => 5,
						'max' => 100,
					],
				],
				'default'    => self::size( 18, 'vh' ),
				'selectors'  => [
					'{{WRAPPER}} .nd-pt__panel .nd-pt__img--sub' => 'max-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'images_object_fit',
			[
				'label'     => esc_html__( 'Object fit', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'cover'      => esc_html__( 'Cover', 'timeline-scroll-content' ),
					'contain'    => esc_html__( 'Contain', 'timeline-scroll-content' ),
					'fill'       => esc_html__( 'Fill', 'timeline-scroll-content' ),
					'scale-down' => esc_html__( 'Scale down', 'timeline-scroll-content' ),
				],
				'default'   => 'cover',
				'selectors' => [
					$img => 'object-fit: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'images_radius',
			[
				'label'          => esc_html__( 'Border radius', 'timeline-scroll-content' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', '%' ],
				'range'          => [
					'px' => [
						'min' => 0,
						'max' => 60,
					],
				],
				'default'        => self::size( 12 ),
				'mobile_default' => self::size( 10 ),
				'selectors'      => [
					$img => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'images_bg',
			[
				'label'     => esc_html__( 'Placeholder background', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(29, 34, 36, 0.06)',
				'global'    => [ 'active' => true ],
				'selectors' => [
					$img => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'images_border',
				'selector' => $img,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'images_box_shadow',
				'selector' => $img,
			]
		);

		$this->start_controls_tabs( 'images_tabs' );

		$this->start_controls_tab(
			'images_tab_normal',
			[ 'label' => esc_html__( 'Normal', 'timeline-scroll-content' ) ]
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name'     => 'images_css_filters',
				'selector' => $img,
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'images_tab_hover',
			[ 'label' => esc_html__( 'Hover', 'timeline-scroll-content' ) ]
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name'     => 'images_css_filters_hover',
				'selector' => $img . ':hover',
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	private function register_style_animation() {
		$this->start_controls_section(
			'style_animation',
			[
				'label' => esc_html__( 'Animation', 'timeline-scroll-content' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'speed_steps',
			[
				'label'     => esc_html__( 'Step transition (ms)', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3000,
				'step'      => 50,
				'default'   => 400,
				'selectors' => [
					'{{WRAPPER}} .nd-pt' => '--nd-pt-speed: {{VALUE}}ms;',
				],
			]
		);

		$this->add_control(
			'speed_detail',
			[
				'label'     => esc_html__( 'Detail panel transition (ms)', 'timeline-scroll-content' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3000,
				'step'      => 50,
				'default'   => 450,
				'selectors' => [
					'{{WRAPPER}} .nd-pt' => '--nd-pt-detail-speed: {{VALUE}}ms;',
				],
				'condition' => [ 'show_detail' => 'yes' ],
			]
		);

		$this->add_control(
			'detail_shift',
			[
				'label'      => esc_html__( 'Detail slide distance', 'timeline-scroll-content' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 120,
					],
				],
				'default'    => self::size( 14 ),
				'selectors'  => [
					'{{WRAPPER}} .nd-pt' => '--nd-pt-shift: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'show_detail' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	private function get_tag_options() {
		return [
			'h1'   => 'H1',
			'h2'   => 'H2',
			'h3'   => 'H3',
			'h4'   => 'H4',
			'h5'   => 'H5',
			'h6'   => 'H6',
			'div'  => 'div',
			'span' => 'span',
			'p'    => 'p',
		];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	protected function render() {
		$settings = $this->get_settings_for_display();
		$steps    = ! empty( $settings['steps'] ) && is_array( $settings['steps'] ) ? $settings['steps'] : [];

		if ( empty( $steps ) ) {
			return;
		}

		$show_detail = 'yes' === $settings['show_detail'];
		$step_tag    = Utils::validate_html_tag( $settings['step_title_tag'] );
		$trigger     = isset( $settings['trigger_line']['size'] ) ? (float) $settings['trigger_line']['size'] : 50;

		$this->add_render_attribute( 'root', 'class', 'nd-pt' );
		if ( ! $show_detail ) {
			$this->add_render_attribute( 'root', 'class', 'nd-pt--no-detail' );
		}
		$this->add_render_attribute( 'root', 'data-nd-trigger', $trigger );
		if ( ! empty( $settings['aria_label'] ) ) {
			$this->add_render_attribute( 'root', 'aria-label', $settings['aria_label'] );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="nd-pt__layout">

				<?php if ( $show_detail ) : ?>
					<div class="nd-pt__panel" aria-live="polite">
						<?php foreach ( $steps as $index => $item ) : ?>
							<div class="nd-pt__detail<?php echo 0 === $index ? ' is-current' : ''; ?>" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
								<?php $this->render_detail( $item, $settings, $index, 'panel' ); ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="nd-pt__timeline">
					<div class="nd-pt__rail" aria-hidden="true">
						<div class="nd-pt__rail-fill"></div>
					</div>
					<ol class="nd-pt__steps">
						<?php foreach ( $steps as $index => $item ) : ?>
							<li class="nd-pt__step">
								<span class="nd-pt__node" aria-hidden="true"><?php echo esc_html( $index + 1 ); ?></span>
								<div class="nd-pt__content">
									<?php
									if ( ! empty( $item['step_title'] ) ) {
										printf(
											'<%1$s class="nd-pt__step-title">%2$s</%1$s>',
											esc_attr( $step_tag ),
											esc_html( $item['step_title'] )
										);
									}

									if ( 'yes' === $settings['show_step_text'] && ! empty( $item['step_text'] ) ) {
										printf( '<p class="nd-pt__text">%s</p>', wp_kses_post( $item['step_text'] ) );
									}

									if ( $show_detail ) {
										echo '<div class="nd-pt__inline">';
										$this->render_detail( $item, $settings, $index, 'inline' );
										echo '</div>';
									}
									?>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>

			</div>
		</section>
		<?php
	}

	/**
	 * Detail markup. Used twice per step: the sticky panel (desktop) and inside the step (stacked layout).
	 *
	 * @param array  $item     Repeater item.
	 * @param array  $settings Widget settings.
	 * @param int    $index    Step index.
	 * @param string $context  "panel" or "inline" (keeps render-attribute keys unique).
	 */
	private function render_detail( array $item, array $settings, $index, $context ) {
		$heading = isset( $item['detail_heading'] ) ? $item['detail_heading'] : '';

		if ( 'yes' === $settings['show_eyebrow'] && ! empty( $item['detail_subtitle'] ) ) {
			printf( '<span class="nd-pt__eyebrow">%s</span>', esc_html( $item['detail_subtitle'] ) );
		}

		if ( 'yes' === $settings['show_detail_heading'] && '' !== $heading ) {
			printf(
				'<%1$s class="nd-pt__heading">%2$s</%1$s>',
				esc_attr( Utils::validate_html_tag( $settings['detail_heading_tag'] ) ),
				esc_html( $heading )
			);
		}

		if ( 'yes' === $settings['show_detail_body'] && ! empty( $item['detail_body'] ) ) {
			printf( '<p class="nd-pt__body">%s</p>', wp_kses_post( $item['detail_body'] ) );
		}

		if ( 'yes' !== $settings['show_images'] ) {
			return;
		}

		$images = [];
		for ( $n = 1; $n <= 3; $n++ ) {
			$key = 'image_' . $n;
			if ( ! empty( $item[ $key ]['url'] ) || ! empty( $item[ $key ]['id'] ) ) {
				$images[] = $item[ $key ];
			}
		}

		if ( empty( $images ) ) {
			return;
		}

		$fallback_alt = ! empty( $item['images_alt'] ) ? $item['images_alt'] : $heading;

		printf( '<div class="nd-pt__media%s">', 2 === count( $images ) ? ' nd-pt__media--2' : '' );

		foreach ( $images as $i => $image ) {
			$key   = sprintf( 'img-%s-%d-%d', $context, $index, $i );
			$attrs = $this->get_image_attributes( $image, $settings, $fallback_alt );

			if ( empty( $attrs['src'] ) ) {
				continue;
			}

			$attrs['class'] = 'nd-pt__img ' . ( 0 === $i ? 'nd-pt__img--main' : 'nd-pt__img--sub' );

			// Only the very first image of the widget may be eager / high priority.
			if ( 0 === $index && 0 === $i && 'eager' === $settings['image_loading'] ) {
				$attrs['loading']       = 'eager';
				$attrs['fetchpriority'] = 'high';
			} else {
				$attrs['loading'] = 'lazy';
			}
			$attrs['decoding'] = 'async';

			$this->add_render_attribute( $key, $attrs );
			?>
			<img <?php $this->print_render_attribute_string( $key ); ?>>
			<?php
		}

		echo '</div>';
	}

	/**
	 * Build the <img> attributes from a media control value.
	 * Uses the widget-level image size, adds width / height to prevent layout shift.
	 *
	 * @param array  $image        Media control value.
	 * @param array  $settings     Widget settings (holds thumbnail_size).
	 * @param string $fallback_alt Alt text used when the attachment has none.
	 * @return array
	 */
	private function get_image_attributes( array $image, array $settings, $fallback_alt ) {
		$id    = ! empty( $image['id'] ) ? (int) $image['id'] : 0;
		$attrs = [
			'src' => '',
			'alt' => '',
		];

		if ( $id ) {
			$size = ! empty( $settings['thumbnail_size'] ) ? $settings['thumbnail_size'] : 'large';
			$src  = Group_Control_Image_Size::get_attachment_image_src( $id, 'thumbnail', $settings );

			$attrs['src'] = $src ? $src : ( isset( $image['url'] ) ? $image['url'] : '' );

			if ( 'custom' === $size ) {
				$dim = ! empty( $settings['thumbnail_custom_dimension'] ) ? $settings['thumbnail_custom_dimension'] : [];
				if ( ! empty( $dim['width'] ) && ! empty( $dim['height'] ) ) {
					$attrs['width']  = (int) $dim['width'];
					$attrs['height'] = (int) $dim['height'];
				}
			} else {
				$meta = wp_get_attachment_image_src( $id, $size );
				if ( $meta ) {
					$attrs['width']  = (int) $meta[1];
					$attrs['height'] = (int) $meta[2];
				}

				$srcset = wp_get_attachment_image_srcset( $id, $size );
				if ( $srcset ) {
					$attrs['srcset'] = $srcset;
					$attrs['sizes']  = '(max-width: 1024px) 100vw, 50vw';
				}
			}

			$attrs['alt'] = Control_Media::get_image_alt( $image );
		} elseif ( ! empty( $image['url'] ) ) {
			$attrs['src'] = $image['url'];
		}

		if ( '' === $attrs['alt'] ) {
			$attrs['alt'] = $fallback_alt;
		}

		return $attrs;
	}

	/* ---------------------------------------------------------------------
	 * Editor live preview (must match render())
	 * ------------------------------------------------------------------ */

	protected function content_template() {
		?>
		<#
		var steps = settings.steps || [];

		if ( steps.length ) {
			var esc         = _.escape,
				showDetail  = 'yes' === settings.show_detail,
				stepTag     = elementor.helpers.validateHTMLTag( settings.step_title_tag ),
				headingTag  = elementor.helpers.validateHTMLTag( settings.detail_heading_tag ),
				trigger     = ( settings.trigger_line && settings.trigger_line.size ) ? settings.trigger_line.size : 50,
				rootClass   = 'nd-pt' + ( showDetail ? '' : ' nd-pt--no-detail' );

			var imageUrl = function( image ) {
				if ( ! image || ( ! image.url && ! image.id ) ) {
					return '';
				}
				if ( ! image.id ) {
					return image.url;
				}
				return elementor.imagesManager.getImageUrl( {
					id: image.id,
					url: image.url,
					size: settings.thumbnail_size,
					dimension: settings.thumbnail_custom_dimension,
					model: view.getEditModel()
				} ) || image.url;
			};

			var renderDetail = function( item, index ) {
				var out = '',
					heading = item.detail_heading || '';

				if ( 'yes' === settings.show_eyebrow && item.detail_subtitle ) {
					out += '<span class="nd-pt__eyebrow">' + esc( item.detail_subtitle ) + '</span>';
				}

				if ( 'yes' === settings.show_detail_heading && heading ) {
					out += '<' + headingTag + ' class="nd-pt__heading">' + esc( heading ) + '</' + headingTag + '>';
				}

				if ( 'yes' === settings.show_detail_body && item.detail_body ) {
					out += '<p class="nd-pt__body">' + item.detail_body + '</p>';
				}

				if ( 'yes' === settings.show_images ) {
					var urls = [];
					_.each( [ item.image_1, item.image_2, item.image_3 ], function( image ) {
						var url = imageUrl( image );
						if ( url ) {
							urls.push( url );
						}
					} );

					if ( urls.length ) {
						var alt = item.images_alt || heading;

						out += '<div class="nd-pt__media' + ( 2 === urls.length ? ' nd-pt__media--2' : '' ) + '">';
						_.each( urls, function( url, i ) {
							var eager = ( 0 === index && 0 === i && 'eager' === settings.image_loading );
							out += '<img class="nd-pt__img ' + ( 0 === i ? 'nd-pt__img--main' : 'nd-pt__img--sub' ) + '"' +
								' src="' + esc( url ) + '" alt="' + esc( alt ) + '"' +
								' loading="' + ( eager ? 'eager' : 'lazy' ) + '"' +
								( eager ? ' fetchpriority="high"' : '' ) +
								' decoding="async">';
						} );
						out += '</div>';
					}
				}

				return out;
			};
		#>
		<section class="{{ rootClass }}" data-nd-trigger="{{ trigger }}"<# if ( settings.aria_label ) { #> aria-label="{{ settings.aria_label }}"<# } #>>

			<div class="nd-pt__layout">

				<# if ( showDetail ) { #>
					<div class="nd-pt__panel" aria-live="polite">
						<# _.each( steps, function( item, index ) { #>
							<div class="nd-pt__detail<# if ( 0 === index ) { #> is-current<# } #>" aria-hidden="{{ 0 === index ? 'false' : 'true' }}">
								{{{ renderDetail( item, index ) }}}
							</div>
						<# } ); #>
					</div>
				<# } #>

				<div class="nd-pt__timeline">
					<div class="nd-pt__rail" aria-hidden="true">
						<div class="nd-pt__rail-fill"></div>
					</div>
					<ol class="nd-pt__steps">
						<# _.each( steps, function( item, index ) { #>
							<li class="nd-pt__step">
								<span class="nd-pt__node" aria-hidden="true">{{{ index + 1 }}}</span>
								<div class="nd-pt__content">
									<# if ( item.step_title ) { #>
										<{{{ stepTag }}} class="nd-pt__step-title">{{ item.step_title }}</{{{ stepTag }}}>
									<# } #>
									<# if ( 'yes' === settings.show_step_text && item.step_text ) { #>
										<p class="nd-pt__text">{{{ item.step_text }}}</p>
									<# } #>
									<# if ( showDetail ) { #>
										<div class="nd-pt__inline">{{{ renderDetail( item, index ) }}}</div>
									<# } #>
								</div>
							</li>
						<# } ); #>
					</ol>
				</div>

			</div>
		</section>
		<# } #>
		<?php
	}
}
