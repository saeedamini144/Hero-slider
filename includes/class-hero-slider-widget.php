<?php
namespace HeroSlider\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Hero_Slider_Widget extends Widget_Base {

	/** Selector prefix for CSS-variable based controls. */
	const R = '{{WRAPPER}} .hs-hero';

	const SVG_NEXT = '<svg class="hs-ico" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
	const SVG_PREV = '<svg class="hs-ico" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>';

	public function get_name() {
		return 'hs-hero-slider';
	}

	public function get_title() {
		return esc_html__( 'اسلایدر هیرو', 'hero-slider' );
	}

	public function get_icon() {
		return 'eicon-slides';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	public function get_keywords() {
		return [ 'slider', 'hero', 'carousel', 'slides', 'اسلایدر', 'هیرو' ];
	}

	public function get_style_depends(): array {
		return [ 'hs-hero-slider' ];
	}

	public function get_script_depends(): array {
		return [ 'hs-hero-slider' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* -----------------------------------------------------------------
	 * Option lists
	 * -------------------------------------------------------------- */

	public static function transitions() {
		return [
			'fade'     => esc_html__( 'محو شدن (Fade)', 'hero-slider' ),
			'slide'    => esc_html__( 'اسلاید افقی', 'hero-slider' ),
			'slide-v'  => esc_html__( 'اسلاید عمودی', 'hero-slider' ),
			'zoom-in'  => esc_html__( 'زوم به داخل', 'hero-slider' ),
			'zoom-out' => esc_html__( 'زوم به بیرون', 'hero-slider' ),
			'blur'     => esc_html__( 'محو با بلور', 'hero-slider' ),
			'circle'   => esc_html__( 'آشکارسازی دایره‌ای', 'hero-slider' ),
			'wipe'     => esc_html__( 'پرده کشویی (Wipe)', 'hero-slider' ),
			'curtain'  => esc_html__( 'پرده از وسط', 'hero-slider' ),
			'diamond'  => esc_html__( 'آشکارسازی لوزی', 'hero-slider' ),
			'rotate'   => esc_html__( 'چرخش و زوم', 'hero-slider' ),
			'none'     => esc_html__( 'بدون افکت', 'hero-slider' ),
		];
	}

	public static function motions() {
		return [
			'none'      => esc_html__( 'بدون حرکت', 'hero-slider' ),
			'zoom-in'   => esc_html__( 'زوم آرام به داخل', 'hero-slider' ),
			'zoom-out'  => esc_html__( 'زوم آرام به بیرون', 'hero-slider' ),
			'pan-left'  => esc_html__( 'حرکت به چپ', 'hero-slider' ),
			'pan-right' => esc_html__( 'حرکت به راست', 'hero-slider' ),
			'pan-up'    => esc_html__( 'حرکت به بالا', 'hero-slider' ),
			'pan-down'  => esc_html__( 'حرکت به پایین', 'hero-slider' ),
			'ken-burns' => esc_html__( 'کن برنز (زوم + حرکت مورب)', 'hero-slider' ),
		];
	}

	public static function content_animations() {
		return [
			'rise'   => esc_html__( 'بالا آمدن', 'hero-slider' ),
			'fade'   => esc_html__( 'محو شدن', 'hero-slider' ),
			'slide'  => esc_html__( 'ورود از کنار', 'hero-slider' ),
			'zoom'   => esc_html__( 'زوم', 'hero-slider' ),
			'blur'   => esc_html__( 'بلور', 'hero-slider' ),
			'reveal' => esc_html__( 'آشکارسازی (Mask)', 'hero-slider' ),
			'none'   => esc_html__( 'بدون انیمیشن', 'hero-slider' ),
		];
	}

	private static function tag_options() {
		return [
			'h1'   => 'H1',
			'h2'   => 'H2',
			'h3'   => 'H3',
			'h4'   => 'H4',
			'h5'   => 'H5',
			'h6'   => 'H6',
			'div'  => 'div',
			'p'    => 'p',
			'span' => 'span',
		];
	}

	private static function valid_tag( $tag, $fallback ) {
		return isset( self::tag_options()[ $tag ] ) ? $tag : $fallback;
	}

	private static function image_size_options() {
		$sizes = [];
		foreach ( get_intermediate_image_sizes() as $size ) {
			$sizes[ $size ] = ucwords( str_replace( [ '_', '-' ], ' ', $size ) );
		}
		$sizes['full'] = esc_html__( 'اندازه کامل', 'hero-slider' );
		return $sizes;
	}

	/** Start / center / end choices with icons that follow the site direction. */
	private static function align_choices() {
		$rtl = is_rtl();
		return [
			'start'  => [
				'title' => esc_html__( 'ابتدا', 'hero-slider' ),
				'icon'  => $rtl ? 'eicon-text-align-right' : 'eicon-text-align-left',
			],
			'center' => [
				'title' => esc_html__( 'وسط', 'hero-slider' ),
				'icon'  => 'eicon-text-align-center',
			],
			'end'    => [
				'title' => esc_html__( 'انتها', 'hero-slider' ),
				'icon'  => $rtl ? 'eicon-text-align-left' : 'eicon-text-align-right',
			],
		];
	}

	private static function dims_var( $var ) {
		return [ self::R => $var . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ];
	}

	private function default_slides() {
		$placeholder = [ 'url' => Utils::get_placeholder_image_src() ];
		$items       = [
			[ 'دکوراسیون داخلی مینیمال', "طراحی با\nهدف", 'هر قطعه در فضای شما باید دلیلی برای بودن داشته باشد. ما خانه‌هایی می‌سازیم آرام، کاربردی و ماندگار؛ با متریال طبیعی و رنگ‌هایی که چشم را خسته نمی‌کنند.', 'ایده‌های طراحی را ببینید' ],
			[ 'نشیمن و پذیرایی', "آرامش در\nجزئیات", 'چیدمانی که با نور روز هماهنگ است و جای نفس کشیدن دارد. مبلمان کم‌حجم، بافت‌های گرم و فضای خالیِ حساب‌شده.', 'نمونه‌کارهای نشیمن' ],
			[ 'سرویس و حمام', "سادگی\nماندگار", 'خطوط تمیز، سطوح یکدست و نگهداری آسان. حمامی که هر روز صبح حس تازگی می‌دهد.', 'طرح‌های حمام' ],
			[ 'مشاوره رایگان', "خانه‌ای که\nشبیه شماست", 'از اولین طرح تا اجرای نهایی کنارتان هستیم. سبک زندگی‌تان را بگویید، بقیه‌اش با ما.', 'درخواست مشاوره' ],
		];

		$slides = [];
		foreach ( $items as $item ) {
			$slides[] = [
				'subtitle'    => $item[0],
				'title'       => $item[1],
				'description' => $item[2],
				'button_text' => $item[3],
				'button_link' => [ 'url' => '#' ],
				'image'       => $placeholder,
			];
		}
		return $slides;
	}

	/* -----------------------------------------------------------------
	 * Controls
	 * -------------------------------------------------------------- */

	protected function register_controls() {
		$this->register_slides_controls();
		$this->register_settings_controls();

		$this->register_layout_style();
		$this->register_image_style();
		$this->register_content_animation_style();
		$this->register_subtitle_style();
		$this->register_title_style();
		$this->register_description_style();
		$this->register_button_style();
		$this->register_arrows_style();
		$this->register_thumbs_style();
		$this->register_dots_style();
	}

	private function register_slides_controls() {
		$this->start_controls_section( 'section_slides', [
			'label' => esc_html__( 'اسلایدها', 'hero-slider' ),
		] );

		$r = new Repeater();

		$r->start_controls_tabs( 'slide_tabs' );

		/* ---------- Tab: content ---------- */
		$r->start_controls_tab( 'slide_tab_content', [
			'label' => esc_html__( 'محتوا', 'hero-slider' ),
		] );

		$r->add_control( 'subtitle', [
			'label'       => esc_html__( 'زیرعنوان', 'hero-slider' ),
			'type'        => Controls_Manager::TEXT,
			'label_block' => true,
			'dynamic'     => [ 'active' => true ],
		] );

		$r->add_control( 'title', [
			'label'       => esc_html__( 'عنوان', 'hero-slider' ),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'default'     => esc_html__( 'عنوان اسلاید', 'hero-slider' ),
			'description' => esc_html__( 'برای رفتن به خط بعد Enter بزنید.', 'hero-slider' ),
			'dynamic'     => [ 'active' => true ],
		] );

		$r->add_control( 'description', [
			'label'   => esc_html__( 'متن زیر عنوان', 'hero-slider' ),
			'type'    => Controls_Manager::TEXTAREA,
			'rows'    => 4,
			'dynamic' => [ 'active' => true ],
		] );

		$r->add_control( 'button_text', [
			'label'       => esc_html__( 'عنوان دکمه', 'hero-slider' ),
			'type'        => Controls_Manager::TEXT,
			'label_block' => true,
			'separator'   => 'before',
			'default'     => esc_html__( 'بیشتر بدانید', 'hero-slider' ),
			'dynamic'     => [ 'active' => true ],
		] );

		$r->add_control( 'button_link', [
			'label'       => esc_html__( 'لینک دکمه', 'hero-slider' ),
			'type'        => Controls_Manager::URL,
			'placeholder' => 'https://',
			'default'     => [ 'url' => '#' ],
			'dynamic'     => [ 'active' => true ],
		] );

		$r->add_control( 'button_icon', [
			'label'       => esc_html__( 'آیکن دکمه', 'hero-slider' ),
			'type'        => Controls_Manager::ICONS,
			'skin'        => 'inline',
			'label_block' => false,
			'description' => esc_html__( 'اگر خالی بماند، آیکن پیش‌فرض (تب استایل › دکمه) استفاده می‌شود.', 'hero-slider' ),
		] );

		$r->end_controls_tab();

		/* ---------- Tab: image ---------- */
		$r->start_controls_tab( 'slide_tab_image', [
			'label' => esc_html__( 'تصویر', 'hero-slider' ),
		] );

		$r->add_control( 'image', [
			'label'   => esc_html__( 'تصویر اسلاید', 'hero-slider' ),
			'type'    => Controls_Manager::MEDIA,
			'default' => [ 'url' => Utils::get_placeholder_image_src() ],
			'dynamic' => [ 'active' => true ],
		] );

		$r->add_control( 'transition', [
			'label'   => esc_html__( 'افکت ورود تصویر', 'hero-slider' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '',
			'options' => [ '' => esc_html__( 'پیش‌فرض (از تب استایل)', 'hero-slider' ) ] + self::transitions(),
		] );

		$r->add_control( 'motion', [
			'label'   => esc_html__( 'حرکت تصویر', 'hero-slider' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '',
			'options' => [ '' => esc_html__( 'پیش‌فرض (از تب استایل)', 'hero-slider' ) ] + self::motions(),
		] );

		$r->add_responsive_control( 'image_position', [
			'label'                => esc_html__( 'محل قرارگیری تصویر', 'hero-slider' ),
			'type'                 => Controls_Manager::SELECT,
			'separator'            => 'before',
			'default'              => '',
			'options'              => [
				''              => esc_html__( 'پیش‌فرض', 'hero-slider' ),
				'center center' => esc_html__( 'وسط وسط', 'hero-slider' ),
				'center top'    => esc_html__( 'وسط بالا', 'hero-slider' ),
				'center bottom' => esc_html__( 'وسط پایین', 'hero-slider' ),
				'left center'   => esc_html__( 'چپ وسط', 'hero-slider' ),
				'left top'      => esc_html__( 'چپ بالا', 'hero-slider' ),
				'left bottom'   => esc_html__( 'چپ پایین', 'hero-slider' ),
				'right center'  => esc_html__( 'راست وسط', 'hero-slider' ),
				'right top'     => esc_html__( 'راست بالا', 'hero-slider' ),
				'right bottom'  => esc_html__( 'راست پایین', 'hero-slider' ),
				'custom'        => esc_html__( 'سفارشی', 'hero-slider' ),
			],
			'selectors_dictionary' => [
				'custom' => 'var(--hs-px, 50%) var(--hs-py, 50%)',
			],
			'selectors'            => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__img' => 'object-position: {{VALUE}};',
			],
		] );

		$r->add_responsive_control( 'image_position_x', [
			'label'      => esc_html__( 'موقعیت افقی (%)', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ '%' ],
			'range'      => [ '%' => [ 'min' => 0, 'max' => 100 ] ],
			'condition'  => [ 'image_position' => 'custom' ],
			'selectors'  => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__img' => '--hs-px: {{SIZE}}%;',
			],
		] );

		$r->add_responsive_control( 'image_position_y', [
			'label'      => esc_html__( 'موقعیت عمودی (%)', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ '%' ],
			'range'      => [ '%' => [ 'min' => 0, 'max' => 100 ] ],
			'condition'  => [ 'image_position' => 'custom' ],
			'selectors'  => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__img' => '--hs-py: {{SIZE}}%;',
			],
		] );

		$r->add_control( 'image_fit', [
			'label'     => esc_html__( 'نحوه نمایش تصویر', 'hero-slider' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => [
				''        => esc_html__( 'پوشش کامل (Cover)', 'hero-slider' ),
				'contain' => esc_html__( 'نمایش کامل (Contain)', 'hero-slider' ),
				'fill'    => esc_html__( 'کشیده (Fill)', 'hero-slider' ),
			],
			'selectors' => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__img' => 'object-fit: {{VALUE}};',
			],
		] );

		$r->add_control( 'overlay', [
			'label'     => esc_html__( 'لایه روی تصویر', 'hero-slider' ),
			'type'      => Controls_Manager::SWITCHER,
			'separator' => 'before',
		] );

		$r->add_control( 'overlay_type', [
			'label'                => esc_html__( 'نوع لایه', 'hero-slider' ),
			'type'                 => Controls_Manager::CHOOSE,
			'default'              => 'color',
			'toggle'               => false,
			'options'              => [
				'color'    => [
					'title' => esc_html__( 'رنگ', 'hero-slider' ),
					'icon'  => 'eicon-paint-brush',
				],
				'gradient' => [
					'title' => esc_html__( 'گرادیانت', 'hero-slider' ),
					'icon'  => 'eicon-barcode',
				],
			],
			'selectors_dictionary' => [
				'color'    => 'background: var(--hs-o1, rgba(0,0,0,.4));',
				'gradient' => 'background: linear-gradient(var(--hs-oa, 180deg), var(--hs-o1, rgba(0,0,0,.4)) var(--hs-os1, 0%), var(--hs-o2, transparent) var(--hs-os2, 100%));',
			],
			'selectors'            => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__overlay' => '{{VALUE}}',
			],
			'condition'            => [ 'overlay' => 'yes' ],
		] );

		$r->add_control( 'overlay_color', [
			'label'     => esc_html__( 'رنگ', 'hero-slider' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => 'rgba(0,0,0,0.4)',
			'selectors' => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__overlay' => '--hs-o1: {{VALUE}};',
			],
			'condition' => [ 'overlay' => 'yes' ],
		] );

		$r->add_control( 'overlay_color_b', [
			'label'     => esc_html__( 'رنگ دوم', 'hero-slider' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => 'rgba(0,0,0,0)',
			'selectors' => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__overlay' => '--hs-o2: {{VALUE}};',
			],
			'condition' => [
				'overlay'      => 'yes',
				'overlay_type' => 'gradient',
			],
		] );

		$r->add_control( 'overlay_angle', [
			'label'      => esc_html__( 'زاویه', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'deg' ],
			'range'      => [ 'deg' => [ 'min' => 0, 'max' => 360, 'step' => 5 ] ],
			'default'    => [ 'unit' => 'deg', 'size' => 180 ],
			'selectors'  => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__overlay' => '--hs-oa: {{SIZE}}deg;',
			],
			'condition'  => [
				'overlay'      => 'yes',
				'overlay_type' => 'gradient',
			],
		] );

		$r->add_control( 'overlay_stop_a', [
			'label'      => esc_html__( 'محل رنگ اول', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ '%' ],
			'default'    => [ 'unit' => '%', 'size' => 0 ],
			'selectors'  => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__overlay' => '--hs-os1: {{SIZE}}%;',
			],
			'condition'  => [
				'overlay'      => 'yes',
				'overlay_type' => 'gradient',
			],
		] );

		$r->add_control( 'overlay_stop_b', [
			'label'      => esc_html__( 'محل رنگ دوم', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ '%' ],
			'default'    => [ 'unit' => '%', 'size' => 100 ],
			'selectors'  => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__overlay' => '--hs-os2: {{SIZE}}%;',
			],
			'condition'  => [
				'overlay'      => 'yes',
				'overlay_type' => 'gradient',
			],
		] );

		$r->add_control( 'overlay_opacity', [
			'label'     => esc_html__( 'شفافیت لایه', 'hero-slider' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ] ],
			'selectors' => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__overlay' => 'opacity: {{SIZE}};',
			],
			'condition' => [ 'overlay' => 'yes' ],
		] );

		$r->add_control( 'overlay_blend', [
			'label'     => esc_html__( 'حالت ترکیب (Blend)', 'hero-slider' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => [
				''            => esc_html__( 'عادی', 'hero-slider' ),
				'multiply'    => 'Multiply',
				'screen'      => 'Screen',
				'overlay'     => 'Overlay',
				'darken'      => 'Darken',
				'lighten'     => 'Lighten',
				'color-dodge' => 'Color Dodge',
				'color-burn'  => 'Color Burn',
				'soft-light'  => 'Soft Light',
				'hue'         => 'Hue',
				'saturation'  => 'Saturation',
				'color'       => 'Color',
				'luminosity'  => 'Luminosity',
			],
			'selectors' => [
				'{{WRAPPER}} {{CURRENT_ITEM}} .hs-bg__overlay' => 'mix-blend-mode: {{VALUE}};',
			],
			'condition' => [ 'overlay' => 'yes' ],
		] );

		$r->end_controls_tab();
		$r->end_controls_tabs();

		$this->add_control( 'slides', [
			'label'       => esc_html__( 'اسلایدها', 'hero-slider' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'default'     => $this->default_slides(),
			'title_field' => '{{{ title ? title.replace( /<[^>]*>/g, " " ) : subtitle }}}',
		] );

		$this->end_controls_section();
	}

	private function register_settings_controls() {
		$this->start_controls_section( 'section_settings', [
			'label' => esc_html__( 'تنظیمات اسلایدر', 'hero-slider' ),
		] );

		$this->add_control( 'image_size', [
			'label'   => esc_html__( 'اندازه تصویر', 'hero-slider' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'full',
			'options' => self::image_size_options(),
		] );

		$this->add_control( 'autoplay', [
			'label'     => esc_html__( 'پخش خودکار', 'hero-slider' ),
			'type'      => Controls_Manager::SWITCHER,
			'default'   => 'yes',
			'separator' => 'before',
		] );

		$this->add_control( 'autoplay_delay', [
			'label'     => esc_html__( 'زمان هر اسلاید (میلی‌ثانیه)', 'hero-slider' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 6000,
			'min'       => 1000,
			'step'      => 500,
			'condition' => [ 'autoplay' => 'yes' ],
		] );

		$this->add_control( 'pause_on_hover', [
			'label'     => esc_html__( 'توقف هنگام هاور', 'hero-slider' ),
			'type'      => Controls_Manager::SWITCHER,
			'default'   => 'yes',
			'condition' => [ 'autoplay' => 'yes' ],
		] );

		$this->add_control( 'keyboard', [
			'label'   => esc_html__( 'کنترل با کیبورد', 'hero-slider' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'swipe', [
			'label'   => esc_html__( 'کشیدن لمسی (Swipe)', 'hero-slider' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'show_thumbs', [
			'label'     => esc_html__( 'بندانگشتی اسلایدهای بعدی', 'hero-slider' ),
			'type'      => Controls_Manager::SWITCHER,
			'default'   => 'yes',
			'separator' => 'before',
		] );

		$this->add_control( 'thumbs_count', [
			'label'     => esc_html__( 'تعداد بندانگشتی', 'hero-slider' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 3,
			'min'       => 1,
			'max'       => 6,
			'condition' => [ 'show_thumbs' => 'yes' ],
		] );

		$this->add_control( 'show_arrows', [
			'label'     => esc_html__( 'دکمه‌های قبلی / بعدی', 'hero-slider' ),
			'type'      => Controls_Manager::SWITCHER,
			'default'   => 'yes',
			'separator' => 'before',
		] );

		$this->add_control( 'arrow_prev_icon', [
			'label'       => esc_html__( 'آیکن قبلی', 'hero-slider' ),
			'type'        => Controls_Manager::ICONS,
			'skin'        => 'inline',
			'label_block' => false,
			'condition'   => [ 'show_arrows' => 'yes' ],
		] );

		$this->add_control( 'arrow_next_icon', [
			'label'       => esc_html__( 'آیکن بعدی', 'hero-slider' ),
			'type'        => Controls_Manager::ICONS,
			'skin'        => 'inline',
			'label_block' => false,
			'condition'   => [ 'show_arrows' => 'yes' ],
		] );

		$this->add_control( 'show_dots', [
			'label'     => esc_html__( 'نقطه‌های شماره اسلاید', 'hero-slider' ),
			'type'      => Controls_Manager::SWITCHER,
			'default'   => 'yes',
			'separator' => 'before',
		] );

		$this->add_control( 'show_progress', [
			'label'     => esc_html__( 'نوار پیشرفت', 'hero-slider' ),
			'type'      => Controls_Manager::SWITCHER,
			'default'   => 'yes',
			'condition' => [ 'autoplay' => 'yes' ],
		] );

		$this->add_control( 'title_decoration', [
			'label'     => esc_html__( 'لوزی تزئینی کنار عنوان', 'hero-slider' ),
			'type'      => Controls_Manager::SWITCHER,
			'default'   => 'yes',
			'separator' => 'before',
		] );

		$this->add_control( 'stack_on', [
			'label'        => esc_html__( 'چیدمان ستونی (زیر هم) از', 'hero-slider' ),
			'type'         => Controls_Manager::SELECT,
			'default'      => 'mobile',
			'options'      => [
				'tablet' => esc_html__( 'تبلت', 'hero-slider' ),
				'mobile' => esc_html__( 'موبایل', 'hero-slider' ),
				'none'   => esc_html__( 'هیچ‌وقت', 'hero-slider' ),
			],
			'prefix_class' => 'hs-stack-',
		] );

		$this->add_control( 'aria_label', [
			'label'   => esc_html__( 'برچسب دسترسی‌پذیری', 'hero-slider' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'اسلایدر اصلی', 'hero-slider' ),
		] );

		$this->end_controls_section();
	}

	/* ---------------------------- Style: layout ---------------------------- */

	private function register_layout_style() {
		$this->start_controls_section( 'section_style_layout', [
			'label' => esc_html__( 'چیدمان', 'hero-slider' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control( 'height', [
			'label'      => esc_html__( 'ارتفاع', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vh', 'em', 'custom' ],
			'range'      => [
				'px' => [ 'min' => 200, 'max' => 1400 ],
				'vh' => [ 'min' => 10, 'max' => 100 ],
			],
			'selectors'  => [ self::R => '--hs-height: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'min_height', [
			'label'      => esc_html__( 'حداقل ارتفاع', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vh', 'custom' ],
			'range'      => [
				'px' => [ 'min' => 0, 'max' => 1200 ],
				'vh' => [ 'min' => 0, 'max' => 100 ],
			],
			'selectors'  => [ self::R => '--hs-min-h: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'bg_color', [
			'label'     => esc_html__( 'رنگ پس‌زمینه', 'hero-slider' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ self::R => '--hs-bg-color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'pad_x', [
			'label'      => esc_html__( 'فاصله افقی از لبه‌ها', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%', 'vw', 'custom' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 300 ] ],
			'selectors'  => [ self::R => '--hs-pad-x: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'col_gap', [
			'label'      => esc_html__( 'فاصله محتوا و ستون کناری', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'custom' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 200 ] ],
			'selectors'  => [ self::R => '--hs-gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'content_width', [
			'label'      => esc_html__( 'حداکثر عرض محتوا', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%', 'custom' ],
			'range'      => [ 'px' => [ 'min' => 200, 'max' => 1200 ] ],
			'selectors'  => [ self::R => '--hs-content-w: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'content_position', [
			'label'     => esc_html__( 'موقعیت افقی محتوا', 'hero-slider' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => self::align_choices(),
			'selectors' => [ self::R => '--hs-content-justify: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'content_valign', [
			'label'     => esc_html__( 'موقعیت عمودی محتوا', 'hero-slider' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => [
				'start'  => [
					'title' => esc_html__( 'بالا', 'hero-slider' ),
					'icon'  => 'eicon-v-align-top',
				],
				'center' => [
					'title' => esc_html__( 'وسط', 'hero-slider' ),
					'icon'  => 'eicon-v-align-middle',
				],
				'end'    => [
					'title' => esc_html__( 'پایین', 'hero-slider' ),
					'icon'  => 'eicon-v-align-bottom',
				],
			],
			'selectors' => [ self::R => '--hs-valign: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'text_align', [
			'label'                => esc_html__( 'تراز متن', 'hero-slider' ),
			'type'                 => Controls_Manager::CHOOSE,
			'options'              => self::align_choices(),
			'selectors_dictionary' => [
				'start'  => '--hs-align: start; --hs-self: flex-start;',
				'center' => '--hs-align: center; --hs-self: center;',
				'end'    => '--hs-align: end; --hs-self: flex-end;',
			],
			'selectors'            => [ self::R => '{{VALUE}}' ],
		] );

		$this->end_controls_section();
	}

	/* ---------------------------- Style: image ---------------------------- */

	private function register_image_style() {
		$this->start_controls_section( 'section_style_image', [
			'label' => esc_html__( 'تصویر اسلایدر و افکت‌ها', 'hero-slider' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'transition', [
			'label'   => esc_html__( 'افکت تعویض اسلاید', 'hero-slider' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'fade',
			'options' => self::transitions(),
		] );

		$this->add_control( 'transition_speed', [
			'label'     => esc_html__( 'سرعت افکت (میلی‌ثانیه)', 'hero-slider' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 1100,
			'min'       => 0,
			'max'       => 5000,
			'step'      => 50,
			'selectors' => [ self::R => '--hs-speed: {{VALUE}}ms;' ],
		] );

		$this->add_control( 'transition_ease', [
			'label'     => esc_html__( 'منحنی حرکت (Easing)', 'hero-slider' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => [
				''                              => esc_html__( 'نرم (پیش‌فرض)', 'hero-slider' ),
				'ease'                          => 'Ease',
				'ease-in-out'                   => 'Ease In Out',
				'linear'                        => 'Linear',
				'cubic-bezier(.77,0,.18,1)'     => esc_html__( 'قوی (Expo)', 'hero-slider' ),
				'cubic-bezier(.34,1.56,.64,1)'  => esc_html__( 'فنری (Back)', 'hero-slider' ),
			],
			'selectors' => [ self::R => '--hs-ease: {{VALUE}};' ],
		] );

		$this->add_control( 'motion', [
			'label'     => esc_html__( 'حرکت تصویر (Ken Burns)', 'hero-slider' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'zoom-out',
			'options'   => self::motions(),
			'separator' => 'before',
		] );

		$this->add_control( 'motion_scale', [
			'label'     => esc_html__( 'شدت زوم حرکت', 'hero-slider' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 1, 'max' => 1.4, 'step' => 0.01 ] ],
			'selectors' => [ self::R => '--hs-kb-scale: {{SIZE}};' ],
			'condition' => [ 'motion!' => 'none' ],
		] );

		$this->add_group_control( Group_Control_Css_Filter::get_type(), [
			'name'      => 'image_filters',
			'selector'  => self::R . ' .hs-bg__img',
			'separator' => 'before',
		] );

		$this->add_control( 'overlay_heading', [
			'label'     => esc_html__( 'لایه خوانایی سمت متن', 'hero-slider' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_control( 'readability_overlay', [
			'label'        => esc_html__( 'فعال', 'hero-slider' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'prefix_class' => 'hs-ov-',
		] );

		$this->add_control( 'readability_color', [
			'label'     => esc_html__( 'رنگ', 'hero-slider' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ self::R => '--hs-ov-color: {{VALUE}};' ],
			'condition' => [ 'readability_overlay' => 'yes' ],
		] );

		$this->add_control( 'readability_opacity', [
			'label'     => esc_html__( 'شدت', 'hero-slider' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.01 ] ],
			'selectors' => [ self::R => '--hs-ov-opacity: {{SIZE}};' ],
			'condition' => [ 'readability_overlay' => 'yes' ],
		] );

		$this->add_control( 'readability_reach', [
			'label'      => esc_html__( 'پوشش', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ '%' ],
			'range'      => [ '%' => [ 'min' => 10, 'max' => 100 ] ],
			'selectors'  => [ self::R => '--hs-ov-reach: {{SIZE}}%;' ],
			'condition'  => [ 'readability_overlay' => 'yes' ],
		] );

		$this->end_controls_section();
	}

	/* ---------------------------- Style: content animation ---------------------------- */

	private function register_content_animation_style() {
		$this->start_controls_section( 'section_style_content_anim', [
			'label' => esc_html__( 'انیمیشن محتوا', 'hero-slider' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'content_animation', [
			'label'   => esc_html__( 'نوع انیمیشن', 'hero-slider' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'rise',
			'options' => self::content_animations(),
		] );

		$this->add_control( 'content_duration', [
			'label'     => esc_html__( 'مدت (میلی‌ثانیه)', 'hero-slider' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 3000,
			'step'      => 50,
			'selectors' => [ self::R => '--hs-c-dur: {{VALUE}}ms;' ],
			'condition' => [ 'content_animation!' => 'none' ],
		] );

		$this->add_control( 'content_stagger', [
			'label'     => esc_html__( 'تأخیر بین اجزا (میلی‌ثانیه)', 'hero-slider' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 1000,
			'step'      => 10,
			'selectors' => [ self::R => '--hs-c-stagger: {{VALUE}}ms;' ],
			'condition' => [ 'content_animation!' => 'none' ],
		] );

		$this->end_controls_section();
	}

	/* ---------------------------- Style: text blocks ---------------------------- */

	private function register_text_controls( $key, $selector, $color_var, $gap_var ) {
		$this->add_control( $key . '_color', [
			'label'     => esc_html__( 'رنگ', 'hero-slider' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ self::R => $color_var . ': {{VALUE}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => $key . '_typography',
			'selector' => self::R . ' ' . $selector,
		] );

		$this->add_group_control( Group_Control_Text_Shadow::get_type(), [
			'name'     => $key . '_shadow',
			'selector' => self::R . ' ' . $selector,
		] );

		$this->add_responsive_control( $key . '_gap', [
			'label'      => esc_html__( 'فاصله پایین', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem', 'custom' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 120 ] ],
			'selectors'  => [ self::R => $gap_var . ': {{SIZE}}{{UNIT}};' ],
		] );
	}

	private function register_subtitle_style() {
		$this->start_controls_section( 'section_style_subtitle', [
			'label' => esc_html__( 'زیرعنوان', 'hero-slider' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'subtitle_tag', [
			'label'   => esc_html__( 'تگ HTML', 'hero-slider' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'p',
			'options' => self::tag_options(),
		] );

		$this->register_text_controls( 'subtitle', '.hs-subtitle', '--hs-sub-color', '--hs-sub-gap' );

		$this->end_controls_section();
	}

	private function register_title_style() {
		$this->start_controls_section( 'section_style_title', [
			'label' => esc_html__( 'عنوان', 'hero-slider' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'title_tag', [
			'label'   => esc_html__( 'تگ HTML', 'hero-slider' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'h2',
			'options' => self::tag_options(),
		] );

		$this->register_text_controls( 'title', '.hs-title', '--hs-title-color', '--hs-title-gap' );

		$this->add_control( 'deco_heading', [
			'label'     => esc_html__( 'لوزی تزئینی', 'hero-slider' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
			'condition' => [ 'title_decoration' => 'yes' ],
		] );

		$this->add_control( 'deco_color', [
			'label'     => esc_html__( 'رنگ خط', 'hero-slider' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ self::R => '--hs-deco-color: {{VALUE}};' ],
			'condition' => [ 'title_decoration' => 'yes' ],
		] );

		$this->add_responsive_control( 'deco_size', [
			'label'      => esc_html__( 'اندازه', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 10, 'max' => 120 ] ],
			'selectors'  => [ self::R => '--hs-deco-size: {{SIZE}}{{UNIT}};' ],
			'condition'  => [ 'title_decoration' => 'yes' ],
		] );

		$this->add_control( 'deco_width', [
			'label'      => esc_html__( 'ضخامت خط', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0.5, 'max' => 6, 'step' => 0.5 ] ],
			'selectors'  => [ self::R => '--hs-deco-bw: {{SIZE}}{{UNIT}};' ],
			'condition'  => [ 'title_decoration' => 'yes' ],
		] );

		$this->end_controls_section();
	}

	private function register_description_style() {
		$this->start_controls_section( 'section_style_desc', [
			'label' => esc_html__( 'متن', 'hero-slider' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->register_text_controls( 'desc', '.hs-desc', '--hs-desc-color', '--hs-desc-gap' );

		$this->add_responsive_control( 'desc_width', [
			'label'      => esc_html__( 'حداکثر عرض', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%', 'ch', 'custom' ],
			'range'      => [
				'px' => [ 'min' => 100, 'max' => 1000 ],
				'ch' => [ 'min' => 10, 'max' => 100 ],
			],
			'selectors'  => [ self::R => '--hs-desc-w: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	/* ---------------------------- Style: button ---------------------------- */

	private function register_button_style() {
		$this->start_controls_section( 'section_style_button', [
			'label' => esc_html__( 'دکمه', 'hero-slider' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'button_icon', [
			'label'       => esc_html__( 'آیکن پیش‌فرض دکمه', 'hero-slider' ),
			'type'        => Controls_Manager::ICONS,
			'skin'        => 'inline',
			'label_block' => false,
			'description' => esc_html__( 'خالی = فلش داخلی سبک. آیکن هر اسلاید در تب محتوا این را جایگزین می‌کند.', 'hero-slider' ),
		] );

		$this->add_control( 'button_icon_style', [
			'label'        => esc_html__( 'سبک آیکن', 'hero-slider' ),
			'type'         => Controls_Manager::SELECT,
			'default'      => 'boxed',
			'options'      => [
				'boxed'  => esc_html__( 'داخل کادر جدا', 'hero-slider' ),
				'inline' => esc_html__( 'کنار متن', 'hero-slider' ),
			],
			'prefix_class' => 'hs-btn-style-',
		] );

		$this->add_control( 'button_icon_position', [
			'label'        => esc_html__( 'محل آیکن', 'hero-slider' ),
			'type'         => Controls_Manager::SELECT,
			'default'      => 'after',
			'options'      => [
				'after'  => esc_html__( 'بعد از متن', 'hero-slider' ),
				'before' => esc_html__( 'قبل از متن', 'hero-slider' ),
			],
			'prefix_class' => 'hs-btn-icon-',
		] );

		$this->add_control( 'button_hover_effect', [
			'label'        => esc_html__( 'افکت هاور', 'hero-slider' ),
			'type'         => Controls_Manager::SELECT,
			'default'      => 'icon',
			'options'      => [
				'none'   => esc_html__( 'بدون افکت', 'hero-slider' ),
				'icon'   => esc_html__( 'حرکت آیکن', 'hero-slider' ),
				'grow'   => esc_html__( 'بزرگ شدن', 'hero-slider' ),
				'shrink' => esc_html__( 'کوچک شدن', 'hero-slider' ),
				'float'  => esc_html__( 'شناور (بالا آمدن)', 'hero-slider' ),
				'shine'  => esc_html__( 'درخشش', 'hero-slider' ),
				'fill'   => esc_html__( 'پر شدن رنگ', 'hero-slider' ),
				'pulse'  => esc_html__( 'پالس', 'hero-slider' ),
			],
			'prefix_class' => 'hs-btn-fx-',
		] );

		$this->add_control( 'button_full_mobile', [
			'label'        => esc_html__( 'تمام‌عرض در موبایل', 'hero-slider' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'prefix_class' => 'hs-btn-full-',
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'      => 'button_typography',
			'selector'  => self::R . ' .hs-btn',
			'separator' => 'before',
		] );

		$this->add_responsive_control( 'button_padding', [
			'label'      => esc_html__( 'پدینگ', 'hero-slider' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em', 'rem', 'custom' ],
			'selectors'  => self::dims_var( '--hs-btn-pad' ),
		] );

		$this->add_responsive_control( 'button_margin', [
			'label'      => esc_html__( 'مارجین', 'hero-slider' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em', 'rem', 'custom' ],
			'selectors'  => self::dims_var( '--hs-btn-margin' ),
		] );

		$this->add_responsive_control( 'button_height', [
			'label'      => esc_html__( 'حداقل ارتفاع', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'custom' ],
			'range'      => [ 'px' => [ 'min' => 20, 'max' => 120 ] ],
			'selectors'  => [ self::R => '--hs-btn-h: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'button_radius', [
			'label'      => esc_html__( 'گردی گوشه‌ها', 'hero-slider' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'custom' ],
			'selectors'  => self::dims_var( '--hs-btn-radius' ),
		] );

		$this->add_control( 'button_border_width', [
			'label'      => esc_html__( 'ضخامت حاشیه', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 10 ] ],
			'selectors'  => [ self::R => '--hs-btn-bw: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'button_icon_heading', [
			'label'     => esc_html__( 'آیکن', 'hero-slider' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_responsive_control( 'button_icon_size', [
			'label'      => esc_html__( 'اندازه آیکن', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'range'      => [ 'px' => [ 'min' => 8, 'max' => 60 ] ],
			'selectors'  => [ self::R => '--hs-btn-icon-size: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'button_icon_box', [
			'label'      => esc_html__( 'عرض کادر آیکن', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'range'      => [ 'px' => [ 'min' => 20, 'max' => 120 ] ],
			'selectors'  => [ self::R => '--hs-btn-icon-w: {{SIZE}}{{UNIT}};' ],
			'condition'  => [ 'button_icon_style' => 'boxed' ],
		] );

		$this->add_responsive_control( 'button_icon_gap', [
			'label'      => esc_html__( 'فاصله آیکن از متن', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
			'selectors'  => [ self::R => '--hs-btn-gap: {{SIZE}}{{UNIT}};' ],
			'condition'  => [ 'button_icon_style' => 'inline' ],
		] );

		$this->start_controls_tabs( 'button_tabs', [ 'separator' => 'before' ] );

		foreach ( [ 'normal' => '', 'hover' => '-h' ] as $state => $suffix ) {
			$this->start_controls_tab( 'button_tab_' . $state, [
				'label' => 'normal' === $state ? esc_html__( 'عادی', 'hero-slider' ) : esc_html__( 'هاور', 'hero-slider' ),
			] );

			$colors = [
				'color'      => [ esc_html__( 'رنگ متن', 'hero-slider' ), '--hs-btn-color' ],
				'bg'         => [ esc_html__( 'رنگ پس‌زمینه', 'hero-slider' ), '--hs-btn-bg' ],
				'icon_color' => [ esc_html__( 'رنگ آیکن', 'hero-slider' ), '--hs-btn-icon-color' ],
				'icon_bg'    => [ esc_html__( 'پس‌زمینه آیکن', 'hero-slider' ), '--hs-btn-icon-bg' ],
				'border'     => [ esc_html__( 'رنگ حاشیه', 'hero-slider' ), '--hs-btn-border' ],
			];

			foreach ( $colors as $key => $color ) {
				$this->add_control( 'button_' . $key . '_' . $state, [
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => [ self::R => $color[1] . $suffix . ': {{VALUE}};' ],
				] );
			}

			$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
				'name'     => 'button_shadow_' . $state,
				'selector' => self::R . ' .hs-btn' . ( 'hover' === $state ? ':hover' : '' ),
			] );

			if ( 'hover' === $state ) {
				$this->add_control( 'button_duration', [
					'label'     => esc_html__( 'مدت انتقال (میلی‌ثانیه)', 'hero-slider' ),
					'type'      => Controls_Manager::NUMBER,
					'min'       => 0,
					'max'       => 2000,
					'step'      => 50,
					'selectors' => [ self::R => '--hs-btn-dur: {{VALUE}}ms;' ],
				] );
			}

			$this->end_controls_tab();
		}

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/* ---------------------------- Style: arrows ---------------------------- */

	private function register_arrows_style() {
		$this->start_controls_section( 'section_style_arrows', [
			'label'     => esc_html__( 'دکمه‌های قبلی / بعدی', 'hero-slider' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_arrows' => 'yes' ],
		] );

		$this->add_responsive_control( 'arrow_size', [
			'label'      => esc_html__( 'اندازه دکمه', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 24, 'max' => 120 ] ],
			'selectors'  => [ self::R => '--hs-arrow-size: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'arrow_icon_size', [
			'label'      => esc_html__( 'اندازه آیکن', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 8, 'max' => 60 ] ],
			'selectors'  => [ self::R => '--hs-arrow-icon: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'arrow_radius', [
			'label'      => esc_html__( 'گردی گوشه‌ها', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%' ],
			'range'      => [
				'px' => [ 'min' => 0, 'max' => 60 ],
				'%'  => [ 'min' => 0, 'max' => 50 ],
			],
			'selectors'  => [ self::R => '--hs-arrow-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'arrow_gap', [
			'label'      => esc_html__( 'فاصله بین دکمه‌ها', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
			'selectors'  => [ self::R => '--hs-arrow-gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'side_gap', [
			'label'      => esc_html__( 'فاصله از بندانگشتی‌ها', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vh' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 200 ] ],
			'selectors'  => [ self::R => '--hs-side-gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->start_controls_tabs( 'arrow_tabs' );
		foreach ( [ 'normal' => '', 'hover' => '-h' ] as $state => $suffix ) {
			$this->start_controls_tab( 'arrow_tab_' . $state, [
				'label' => 'normal' === $state ? esc_html__( 'عادی', 'hero-slider' ) : esc_html__( 'هاور', 'hero-slider' ),
			] );

			$this->add_control( 'arrow_color_' . $state, [
				'label'     => esc_html__( 'رنگ آیکن', 'hero-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ self::R => '--hs-arrow-color' . $suffix . ': {{VALUE}};' ],
			] );

			$this->add_control( 'arrow_bg_' . $state, [
				'label'     => esc_html__( 'رنگ پس‌زمینه', 'hero-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ self::R => '--hs-arrow-bg' . $suffix . ': {{VALUE}};' ],
			] );

			$this->end_controls_tab();
		}
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/* ---------------------------- Style: thumbnails ---------------------------- */

	private function register_thumbs_style() {
		$this->start_controls_section( 'section_style_thumbs', [
			'label'     => esc_html__( 'بندانگشتی‌ها', 'hero-slider' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_thumbs' => 'yes' ],
		] );

		$this->add_responsive_control( 'thumb_size', [
			'label'      => esc_html__( 'عرض', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vw', 'custom' ],
			'range'      => [ 'px' => [ 'min' => 40, 'max' => 400 ] ],
			'selectors'  => [ self::R => '--hs-thumb-size: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'thumb_ratio', [
			'label'     => esc_html__( 'نسبت ابعاد', 'hero-slider' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => [
				''     => '1:1',
				'4/5'  => '4:5',
				'3/4'  => '3:4',
				'2/3'  => '2:3',
				'4/3'  => '4:3',
				'16/9' => '16:9',
			],
			'selectors' => [ self::R => '--hs-thumb-ratio: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'thumb_gap', [
			'label'      => esc_html__( 'فاصله', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
			'selectors'  => [ self::R => '--hs-thumb-gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'thumb_radius', [
			'label'      => esc_html__( 'گردی گوشه‌ها', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'selectors'  => [ self::R => '--hs-thumb-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'thumb_overlay', [
			'label'     => esc_html__( 'رنگ لایه هاور', 'hero-slider' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ self::R => '--hs-thumb-overlay: {{VALUE}};' ],
		] );

		$this->add_control( 'thumb_zoom', [
			'label'     => esc_html__( 'زوم هنگام هاور', 'hero-slider' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 1, 'max' => 1.3, 'step' => 0.01 ] ],
			'selectors' => [ self::R => '--hs-thumb-zoom: {{SIZE}};' ],
		] );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name'     => 'thumb_shadow',
			'selector' => self::R . ' .hs-thumb',
		] );

		$this->end_controls_section();
	}

	/* ---------------------------- Style: dots & progress ---------------------------- */

	private function register_dots_style() {
		$this->start_controls_section( 'section_style_dots', [
			'label' => esc_html__( 'نقطه‌ها و نوار پیشرفت', 'hero-slider' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'dots_heading', [
			'label'     => esc_html__( 'نقطه‌ها', 'hero-slider' ),
			'type'      => Controls_Manager::HEADING,
			'condition' => [ 'show_dots' => 'yes' ],
		] );

		$this->add_responsive_control( 'dots_position', [
			'label'                => esc_html__( 'موقعیت', 'hero-slider' ),
			'type'                 => Controls_Manager::CHOOSE,
			'options'              => self::align_choices(),
			'selectors_dictionary' => [
				'start'  => 'flex-start',
				'center' => 'center',
				'end'    => 'flex-end',
			],
			'selectors'            => [ self::R => '--hs-dots-justify: {{VALUE}};' ],
			'condition'            => [ 'show_dots' => 'yes' ],
		] );

		$this->add_responsive_control( 'dots_bottom', [
			'label'      => esc_html__( 'فاصله از پایین', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 200 ] ],
			'selectors'  => [ self::R => '--hs-dots-bottom: {{SIZE}}{{UNIT}};' ],
			'condition'  => [ 'show_dots' => 'yes' ],
		] );

		$dot_sizes = [
			'dot_width'        => [ esc_html__( 'عرض', 'hero-slider' ), '--hs-dot-w', 100 ],
			'dot_width_active' => [ esc_html__( 'عرض نقطه فعال', 'hero-slider' ), '--hs-dot-w-active', 150 ],
			'dot_height'       => [ esc_html__( 'ارتفاع', 'hero-slider' ), '--hs-dot-h', 30 ],
			'dot_gap'          => [ esc_html__( 'فاصله', 'hero-slider' ), '--hs-dot-gap', 40 ],
			'dot_radius'       => [ esc_html__( 'گردی گوشه‌ها', 'hero-slider' ), '--hs-dot-radius', 30 ],
		];
		foreach ( $dot_sizes as $key => $conf ) {
			$this->add_control( $key, [
				'label'      => $conf[0],
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => $conf[2] ] ],
				'selectors'  => [ self::R => $conf[1] . ': {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'show_dots' => 'yes' ],
			] );
		}

		$dot_colors = [
			'dot_color'        => [ esc_html__( 'رنگ', 'hero-slider' ), '--hs-dot-color' ],
			'dot_color_hover'  => [ esc_html__( 'رنگ هاور', 'hero-slider' ), '--hs-dot-color-h' ],
			'dot_color_active' => [ esc_html__( 'رنگ نقطه فعال', 'hero-slider' ), '--hs-dot-active' ],
		];
		foreach ( $dot_colors as $key => $conf ) {
			$this->add_control( $key, [
				'label'     => $conf[0],
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ self::R => $conf[1] . ': {{VALUE}};' ],
				'condition' => [ 'show_dots' => 'yes' ],
			] );
		}

		$progress_condition = [
			'autoplay'      => 'yes',
			'show_progress' => 'yes',
		];

		$this->add_control( 'progress_heading', [
			'label'     => esc_html__( 'نوار پیشرفت', 'hero-slider' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
			'condition' => $progress_condition,
		] );

		$this->add_control( 'progress_height', [
			'label'      => esc_html__( 'ضخامت', 'hero-slider' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 1, 'max' => 20 ] ],
			'selectors'  => [ self::R => '--hs-prog-h: {{SIZE}}{{UNIT}};' ],
			'condition'  => $progress_condition,
		] );

		$this->add_control( 'progress_color', [
			'label'     => esc_html__( 'رنگ نوار', 'hero-slider' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ self::R => '--hs-prog-color: {{VALUE}};' ],
			'condition' => $progress_condition,
		] );

		$this->add_control( 'progress_track', [
			'label'     => esc_html__( 'رنگ زمینه نوار', 'hero-slider' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ self::R => '--hs-prog-track: {{VALUE}};' ],
			'condition' => $progress_condition,
		] );

		$this->end_controls_section();
	}

	/* -----------------------------------------------------------------
	 * Render helpers
	 * -------------------------------------------------------------- */

	private function icon_html( $icon, $fallback ) {
		if ( empty( $icon['value'] ) ) {
			return $fallback;
		}
		ob_start();
		Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
		$html = ob_get_clean();
		return $html ? $html : $fallback;
	}

	private function image_html( $image, $size, $attrs ) {
		if ( ! empty( $image['id'] ) ) {
			$html = wp_get_attachment_image( $image['id'], $size, false, $attrs );
			if ( $html ) {
				return $html;
			}
		}

		if ( empty( $image['url'] ) ) {
			return '';
		}

		$attrs = array_merge( [ 'alt' => '' ], $attrs, [ 'src' => $image['url'] ] );
		unset( $attrs['sizes'] );
		$out = '<img';
		foreach ( $attrs as $name => $value ) {
			$out .= sprintf( ' %s="%s"', esc_attr( $name ), 'src' === $name ? esc_url( $value ) : esc_attr( $value ) );
		}
		return $out . '>';
	}

	/* -----------------------------------------------------------------
	 * Render
	 * -------------------------------------------------------------- */

	protected function render() {
		$s      = $this->get_settings_for_display();
		$slides = ! empty( $s['slides'] ) && is_array( $s['slides'] ) ? array_values( $s['slides'] ) : [];
		$count  = count( $slides );
		$blank  = [
			'_id'         => '',
			'subtitle'    => '',
			'title'       => '',
			'description' => '',
			'button_text' => '',
			'button_link' => [],
			'button_icon' => [],
			'image'       => [],
			'transition'  => '',
			'motion'      => '',
			'overlay'     => '',
		];
		foreach ( $slides as $i => $slide ) {
			$slides[ $i ] = array_merge( $blank, (array) $slide );
		}

		if ( ! $count ) {
			return;
		}

		$transitions = self::transitions();
		$motions     = self::motions();
		$anims       = self::content_animations();

		$g_fx     = isset( $transitions[ $s['transition'] ] ) ? $s['transition'] : 'fade';
		$g_mo     = isset( $motions[ $s['motion'] ] ) ? $s['motion'] : 'none';
		$anim     = isset( $anims[ $s['content_animation'] ] ) ? $s['content_animation'] : 'rise';
		$multi    = $count > 1;
		$autoplay = $multi && 'yes' === $s['autoplay'];
		$thumbs   = $multi && 'yes' === $s['show_thumbs'];
		$arrows   = $multi && 'yes' === $s['show_arrows'];
		$dots     = $multi && 'yes' === $s['show_dots'];
		$progress = $autoplay && 'yes' === $s['show_progress'];
		$deco     = 'yes' === $s['title_decoration'];
		$thumbs_n = max( 1, min( 6, (int) $s['thumbs_count'] ) );
		$delay    = max( 1000, (int) $s['autoplay_delay'] );
		$size     = ! empty( $s['image_size'] ) ? $s['image_size'] : 'full';
		$t_tag    = self::valid_tag( $s['title_tag'], 'h2' );
		$st_tag   = self::valid_tag( $s['subtitle_tag'], 'p' );
		$btn_icon = $this->icon_html( $s['button_icon'] ?? [], self::SVG_NEXT );

		$config = [
			'autoplay'     => $autoplay,
			'delay'        => $delay,
			'pauseOnHover' => 'yes' === $s['pause_on_hover'],
			'keyboard'     => 'yes' === $s['keyboard'],
			'swipe'        => 'yes' === $s['swipe'],
			'thumbs'       => $thumbs ? $thumbs_n : 0,
		];

		$classes = [ 'hs-hero', 'hs-canim-' . $anim ];
		if ( ! $thumbs && ! $arrows ) {
			$classes[] = 'hs-no-side';
		}
		if ( ! $autoplay ) {
			$classes[] = 'is-paused';
		}

		$this->add_render_attribute( 'root', [
			'class'                => $classes,
			'aria-roledescription' => 'carousel',
			'aria-label'           => $s['aria_label'] ? $s['aria_label'] : esc_html__( 'اسلایدر', 'hero-slider' ),
			'data-hs'              => wp_json_encode( $config ),
			'style'                => '--hs-delay:' . $delay . 'ms',
		] );
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="hs-slides">
				<?php
				foreach ( $slides as $i => $slide ) :
					$fx      = isset( $transitions[ $slide['transition'] ?? '' ] ) ? $slide['transition'] : $g_fx;
					$mo      = isset( $motions[ $slide['motion'] ?? '' ] ) ? $slide['motion'] : $g_mo;
					$bg_attr = [
						'class'    => 'hs-bg__img',
						'loading'  => 0 === $i ? 'eager' : 'lazy',
						'decoding' => 0 === $i ? 'auto' : 'async',
						'sizes'    => '100vw',
					];
					if ( 0 === $i ) {
						$bg_attr['fetchpriority'] = 'high';
					}
					?>
					<div class="hs-bg hs-fx-<?php echo esc_attr( $fx ); ?> hs-mo-<?php echo esc_attr( $mo ); ?> elementor-repeater-item-<?php echo esc_attr( $slide['_id'] ); ?><?php echo 0 === $i ? ' is-active' : ''; ?>">
						<?php echo $this->image_html( $slide['image'] ?? [], $size, $bg_attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( 'yes' === ( $slide['overlay'] ?? '' ) ) : ?>
							<span class="hs-bg__overlay"></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="hs-inner">
				<div class="hs-contents" aria-live="<?php echo $autoplay ? 'off' : 'polite'; ?>">
					<?php
					foreach ( $slides as $i => $slide ) :
						$active = 0 === $i;
						/* translators: 1: slide number, 2: total slides */
						$label = sprintf( esc_html__( '%1$d از %2$d', 'hero-slider' ), $i + 1, $count );
						?>
						<div class="hs-content elementor-repeater-item-<?php echo esc_attr( $slide['_id'] ); ?><?php echo $active ? ' is-active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( $label ); ?>" aria-hidden="<?php echo $active ? 'false' : 'true'; ?>">
							<?php if ( '' !== trim( (string) $slide['subtitle'] ) ) : ?>
								<<?php echo esc_html( $st_tag ); ?> class="hs-subtitle"><?php echo esc_html( $slide['subtitle'] ); ?></<?php echo esc_html( $st_tag ); ?>>
							<?php endif; ?>

							<?php if ( '' !== trim( (string) $slide['title'] ) ) : ?>
								<<?php echo esc_html( $t_tag ); ?> class="hs-title"><?php echo nl2br( wp_kses_post( $slide['title'] ) ); ?><?php if ( $deco ) : ?><span class="hs-deco" aria-hidden="true"></span><?php endif; ?></<?php echo esc_html( $t_tag ); ?>>
							<?php endif; ?>

							<?php if ( '' !== trim( (string) $slide['description'] ) ) : ?>
								<div class="hs-desc"><?php echo nl2br( wp_kses_post( $slide['description'] ) ); ?></div>
							<?php endif; ?>

							<?php
							if ( '' !== trim( (string) $slide['button_text'] ) ) :
								$icon     = $this->icon_html( $slide['button_icon'] ?? [], $btn_icon );
								$has_link = ! empty( $slide['button_link']['url'] );
								$btn_key  = 'button-' . $i;
								$this->add_render_attribute( $btn_key, 'class', 'hs-btn' );
								if ( $has_link ) {
									$this->add_link_attributes( $btn_key, $slide['button_link'] );
								}
								$btn_tag = $has_link ? 'a' : 'span';
								?>
								<<?php echo $btn_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php $this->print_render_attribute_string( $btn_key ); ?>>
									<span class="hs-btn__label"><?php echo esc_html( $slide['button_text'] ); ?></span>
									<span class="hs-btn__icon" aria-hidden="true"><span class="hs-btn__i"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></span>
								</<?php echo $btn_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( $thumbs || $arrows ) : ?>
					<div class="hs-side">
						<?php if ( $thumbs ) : ?>
							<div class="hs-thumbs">
								<?php
								$visible = min( $thumbs_n, $count - 1 );
								foreach ( $slides as $i => $slide ) :
									$k     = $i; // Slide 0 is active, so slide k is the k-th "next" thumbnail.
									$shown = $k >= 1 && $k <= $visible;
									$name  = wp_strip_all_tags( $slide['title'] ? $slide['title'] : $slide['subtitle'] );
									/* translators: %d: slide number */
									$aria = sprintf( esc_html__( 'رفتن به اسلاید %d', 'hero-slider' ), $i + 1 ) . ( $name ? ': ' . $name : '' );
									?>
									<button type="button" class="hs-thumb<?php echo $shown ? ' is-visible' : ''; ?>" data-index="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( $aria ); ?>"<?php echo $shown ? ' data-k="' . (int) $k . '" style="order:' . (int) $k . ';--k:' . (int) ( $k - 1 ) . '"' : ''; ?>>
										<?php
										echo $this->image_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											$slide['image'] ?? [],
											'medium',
											[
												'class'    => 'hs-thumb__img',
												'alt'      => '',
												'loading'  => 'lazy',
												'decoding' => 'async',
												'sizes'    => '200px',
											]
										);
										?>
									</button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( $arrows ) : ?>
							<div class="hs-nav">
								<button type="button" class="hs-arrow hs-arrow--prev" aria-label="<?php echo esc_attr__( 'اسلاید قبلی', 'hero-slider' ); ?>">
									<?php echo $this->icon_html( $s['arrow_prev_icon'] ?? [], self::SVG_PREV ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</button>
								<button type="button" class="hs-arrow hs-arrow--next" aria-label="<?php echo esc_attr__( 'اسلاید بعدی', 'hero-slider' ); ?>">
									<?php echo $this->icon_html( $s['arrow_next_icon'] ?? [], self::SVG_NEXT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</button>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $dots ) : ?>
				<div class="hs-dots">
					<?php for ( $i = 0; $i < $count; $i++ ) : ?>
						<?php /* translators: %d: slide number */ ?>
						<button type="button" class="hs-dot<?php echo 0 === $i ? ' is-active' : ''; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'اسلاید %d', 'hero-slider' ), $i + 1 ) ); ?>" aria-current="<?php echo 0 === $i ? 'true' : 'false'; ?>"></button>
					<?php endfor; ?>
				</div>
			<?php endif; ?>

			<?php if ( $progress ) : ?>
				<div class="hs-progress" aria-hidden="true"><span class="hs-progress__bar"></span></div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Live preview template for the editor (mirrors render()).
	 */
	protected function content_template() {
		?>
		<#
		var slides = settings.slides || [];
		var count = slides.length;
		if ( count ) {
			var TAGS = <?php echo wp_json_encode( array_keys( self::tag_options() ) ); ?>;
			var FX = <?php echo wp_json_encode( array_keys( self::transitions() ) ); ?>;
			var MO = <?php echo wp_json_encode( array_keys( self::motions() ) ); ?>;
			var ANIMS = <?php echo wp_json_encode( array_keys( self::content_animations() ) ); ?>;
			var SVG_NEXT = '<?php echo self::SVG_NEXT; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>';
			var SVG_PREV = '<?php echo self::SVG_PREV; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>';

			var gFx = FX.indexOf( settings.transition ) > -1 ? settings.transition : 'fade';
			var gMo = MO.indexOf( settings.motion ) > -1 ? settings.motion : 'none';
			var anim = ANIMS.indexOf( settings.content_animation ) > -1 ? settings.content_animation : 'rise';
			var multi = count > 1;
			var autoplay = multi && 'yes' === settings.autoplay;
			var thumbs = multi && 'yes' === settings.show_thumbs;
			var arrows = multi && 'yes' === settings.show_arrows;
			var dots = multi && 'yes' === settings.show_dots;
			var progress = autoplay && 'yes' === settings.show_progress;
			var deco = 'yes' === settings.title_decoration;
			var thumbsN = Math.max( 1, Math.min( 6, parseInt( settings.thumbs_count, 10 ) || 3 ) );
			var delay = Math.max( 1000, parseInt( settings.autoplay_delay, 10 ) || 6000 );
			var tTag = TAGS.indexOf( settings.title_tag ) > -1 ? settings.title_tag : 'h2';
			var stTag = TAGS.indexOf( settings.subtitle_tag ) > -1 ? settings.subtitle_tag : 'p';

			var hsIcon = function( icon, fallback ) {
				if ( ! icon || ! icon.value ) {
					return fallback;
				}
				var r = elementor.helpers.renderIcon( view, icon, { 'aria-hidden': true }, 'i', 'object' );
				if ( r && r.rendered ) {
					return r.value;
				}
				if ( 'svg' === icon.library ) {
					return icon.value.url ? '<img src="' + _.escape( icon.value.url ) + '" alt="">' : fallback;
				}
				return '<i class="' + _.escape( icon.value ) + '" aria-hidden="true"></i>';
			};
			var nl2br = function( str ) {
				return ( str || '' ).replace( /\r?\n/g, '<br>' );
			};
			var btnIcon = hsIcon( settings.button_icon, SVG_NEXT );

			var config = {
				autoplay: autoplay,
				delay: delay,
				pauseOnHover: 'yes' === settings.pause_on_hover,
				keyboard: 'yes' === settings.keyboard,
				swipe: 'yes' === settings.swipe,
				thumbs: thumbs ? thumbsN : 0
			};

			var rootClass = [ 'hs-hero', 'hs-canim-' + anim ];
			if ( ! thumbs && ! arrows ) { rootClass.push( 'hs-no-side' ); }
			if ( ! autoplay ) { rootClass.push( 'is-paused' ); }
			var visible = Math.min( thumbsN, count - 1 );
			#>
			<section class="{{ rootClass.join( ' ' ) }}" aria-roledescription="carousel" aria-label="{{ settings.aria_label }}" data-hs="{{ JSON.stringify( config ) }}" style="--hs-delay:{{ delay }}ms">
				<div class="hs-slides">
					<# _.each( slides, function( slide, i ) {
						var fx = FX.indexOf( slide.transition ) > -1 ? slide.transition : gFx;
						var mo = MO.indexOf( slide.motion ) > -1 ? slide.motion : gMo;
					#>
					<div class="hs-bg hs-fx-{{ fx }} hs-mo-{{ mo }} elementor-repeater-item-{{ slide._id }}{{ 0 === i ? ' is-active' : '' }}">
						<# if ( slide.image && slide.image.url ) { #>
							<img class="hs-bg__img" src="{{ slide.image.url }}" alt="">
						<# } #>
						<# if ( 'yes' === slide.overlay ) { #>
							<span class="hs-bg__overlay"></span>
						<# } #>
					</div>
					<# } ); #>
				</div>

				<div class="hs-inner">
					<div class="hs-contents" aria-live="{{ autoplay ? 'off' : 'polite' }}">
						<# _.each( slides, function( slide, i ) {
							var active = 0 === i;
						#>
						<div class="hs-content elementor-repeater-item-{{ slide._id }}{{ active ? ' is-active' : '' }}" role="group" aria-roledescription="slide" aria-label="{{ i + 1 }} / {{ count }}" aria-hidden="{{ active ? 'false' : 'true' }}">
							<# if ( slide.subtitle && slide.subtitle.trim() ) { #>
								<{{ stTag }} class="hs-subtitle">{{ slide.subtitle }}</{{ stTag }}>
							<# } #>
							<# if ( slide.title && slide.title.trim() ) { #>
								<{{ tTag }} class="hs-title">{{{ nl2br( slide.title ) }}}<# if ( deco ) { #><span class="hs-deco" aria-hidden="true"></span><# } #></{{ tTag }}>
							<# } #>
							<# if ( slide.description && slide.description.trim() ) { #>
								<div class="hs-desc">{{{ nl2br( slide.description ) }}}</div>
							<# } #>
							<# if ( slide.button_text && slide.button_text.trim() ) {
								var hasLink = slide.button_link && slide.button_link.url;
								var btnTag = hasLink ? 'a' : 'span';
								var icon = hsIcon( slide.button_icon, btnIcon );
							#>
								<{{ btnTag }} class="hs-btn"<# if ( hasLink ) { #> href="{{ slide.button_link.url }}"<# } #>>
									<span class="hs-btn__label">{{ slide.button_text }}</span>
									<span class="hs-btn__icon" aria-hidden="true"><span class="hs-btn__i">{{{ icon }}}</span></span>
								</{{ btnTag }}>
							<# } #>
						</div>
						<# } ); #>
					</div>

					<# if ( thumbs || arrows ) { #>
					<div class="hs-side">
						<# if ( thumbs ) { #>
						<div class="hs-thumbs">
							<# _.each( slides, function( slide, i ) {
								var shown = i >= 1 && i <= visible;
							#>
							<button type="button" class="hs-thumb{{ shown ? ' is-visible' : '' }}" data-index="{{ i }}" aria-label="{{ i + 1 }}"<# if ( shown ) { #> data-k="{{ i }}" style="order:{{ i }};--k:{{ i - 1 }}"<# } #>>
								<# if ( slide.image && slide.image.url ) { #>
									<img class="hs-thumb__img" src="{{ slide.image.url }}" alt="" loading="lazy">
								<# } #>
							</button>
							<# } ); #>
						</div>
						<# } #>

						<# if ( arrows ) { #>
						<div class="hs-nav">
							<button type="button" class="hs-arrow hs-arrow--prev" aria-label="<?php echo esc_attr__( 'اسلاید قبلی', 'hero-slider' ); ?>">{{{ hsIcon( settings.arrow_prev_icon, SVG_PREV ) }}}</button>
							<button type="button" class="hs-arrow hs-arrow--next" aria-label="<?php echo esc_attr__( 'اسلاید بعدی', 'hero-slider' ); ?>">{{{ hsIcon( settings.arrow_next_icon, SVG_NEXT ) }}}</button>
						</div>
						<# } #>
					</div>
					<# } #>
				</div>

				<# if ( dots ) { #>
				<div class="hs-dots">
					<# for ( var d = 0; d < count; d++ ) { #>
						<button type="button" class="hs-dot{{ 0 === d ? ' is-active' : '' }}" aria-label="{{ d + 1 }}" aria-current="{{ 0 === d ? 'true' : 'false' }}"></button>
					<# } #>
				</div>
				<# } #>

				<# if ( progress ) { #>
				<div class="hs-progress" aria-hidden="true"><span class="hs-progress__bar"></span></div>
				<# } #>
			</section>
		<# } #>
		<?php
	}
}
