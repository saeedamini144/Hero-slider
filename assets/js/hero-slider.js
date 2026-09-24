/*! Hero Slider for Elementor */
(function () {
	'use strict';

	var WIDGET = 'hs-hero-slider';

	class HeroSlider {
		constructor(root) {
			var cfg = {};
			try { cfg = JSON.parse(root.getAttribute('data-hs') || '{}'); } catch (e) {}

			this.root = root;
			this.o = Object.assign({ autoplay: true, delay: 6000, pauseOnHover: true, keyboard: true, swipe: true, thumbs: 3 }, cfg);
			this.bgs = Array.from(root.querySelectorAll('.hs-bg'));
			this.contents = Array.from(root.querySelectorAll('.hs-content'));
			this.thumbs = Array.from(root.querySelectorAll('.hs-thumb'));
			this.thumbWrap = root.querySelector('.hs-thumbs');
			this.dots = Array.from(root.querySelectorAll('.hs-dot'));
			this.bar = root.querySelector('.hs-progress__bar');
			this.live = root.querySelector('.hs-contents');
			this.count = this.bgs.length;
			this.current = Math.max(0, this.bgs.findIndex(function (b) { return b.classList.contains('is-active'); }));
			this.rtl = getComputedStyle(root).direction === 'rtl';
			this.reasons = new Set();
			this.running = false;
			this.remaining = this.o.delay;
			this.startedAt = 0;
			this.timer = 0;
			this.prevTimer = 0;
			this.inView = true;
			this.cleanup = [];

			root.style.setProperty('--hs-delay', this.o.delay + 'ms');
			if (this.count < 2) return;

			if (!this.o.autoplay) this.reasons.add('off');
			root.classList.toggle('is-paused', this.reasons.size > 0);
			this.bind();
			this.restartBar();
			this.update();
		}

		on(target, type, fn, opts) {
			target.addEventListener(type, fn, opts);
			this.cleanup.push(function () { target.removeEventListener(type, fn, opts); });
		}

		bind() {
			var self = this, root = this.root;

			root.querySelectorAll('.hs-arrow--next').forEach(function (b) { self.on(b, 'click', function () { self.next(); }); });
			root.querySelectorAll('.hs-arrow--prev').forEach(function (b) { self.on(b, 'click', function () { self.prev(); }); });
			this.dots.forEach(function (d, i) { self.on(d, 'click', function () { self.go(i); }); });
			this.thumbs.forEach(function (t) { self.on(t, 'click', function () { self.go(+t.getAttribute('data-index'), false); }); });

			if (this.o.pauseOnHover) {
				this.on(root, 'mouseenter', function () { self.pause('hover'); });
				this.on(root, 'mouseleave', function () { self.resume('hover'); });
			}
			this.on(root, 'focusin', function () { self.pause('focus'); });
			this.on(root, 'focusout', function (e) { if (!root.contains(e.relatedTarget)) self.resume('focus'); });
			this.on(document, 'visibilitychange', function () { document.hidden ? self.pause('hidden') : self.resume('hidden'); });

			if ('IntersectionObserver' in window) {
				this.io = new IntersectionObserver(function (entries) {
					self.inView = entries[0].isIntersecting;
					self.inView ? self.resume('offscreen') : self.pause('offscreen');
				});
				this.io.observe(root);
			}

			if (this.o.keyboard) {
				this.on(document, 'keydown', function (e) {
					if (!self.inView || (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight')) return;
					var t = e.target;
					if (t !== document.body && !root.contains(t)) return;
					if (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName)) return;
					var forward = (e.key === 'ArrowLeft') === self.rtl;
					forward ? self.next() : self.prev();
				});
			}

			if (this.o.swipe) {
				var x0 = null, y0 = 0;
				this.on(root, 'touchstart', function (e) { x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; }, { passive: true });
				this.on(root, 'touchend', function (e) {
					if (x0 === null) return;
					var dx = e.changedTouches[0].clientX - x0, dy = e.changedTouches[0].clientY - y0;
					x0 = null;
					if (Math.abs(dx) < 50 || Math.abs(dx) < Math.abs(dy)) return;
					(self.rtl ? dx > 0 : dx < 0) ? self.next() : self.prev();
				}, { passive: true });
			}
		}

		speed() {
			return parseFloat(getComputedStyle(this.root).getPropertyValue('--hs-speed')) || 0;
		}

		next() { this.go(this.current + 1, false); }
		prev() { this.go(this.current - 1, true); }

		go(i, back) {
			if (this.destroyed || this.count < 2) return;
			if (!this.root.isConnected) { this.destroy(); return; }

			var n = this.count, to = ((i % n) + n) % n;
			if (to === this.current) return;
			if (back === undefined) back = i < this.current;

			var root = this.root, fromEl = this.bgs[this.current], toEl = this.bgs[to];
			root.classList.toggle('is-back', back);
			root.classList.add('is-animated');

			this.bgs.forEach(function (b) { if (b !== fromEl) b.classList.remove('is-prev'); });
			fromEl.classList.remove('is-active');
			fromEl.classList.add('is-prev');
			void toEl.offsetWidth; // restart motion if this slide was still leaving
			toEl.classList.add('is-active');

			clearTimeout(this.prevTimer);
			this.prevTimer = setTimeout(function () { fromEl.classList.remove('is-prev'); }, this.speed() + 50);

			this.current = to;
			this.sync();
			this.restartBar();
			this.remaining = this.o.delay;
			if (this.running) this.schedule(this.o.delay);
		}

		sync() {
			var cur = this.current, n = this.count;

			this.contents.forEach(function (c, k) {
				var active = k === cur;
				c.classList.toggle('is-active', active);
				c.setAttribute('aria-hidden', active ? 'false' : 'true');
			});

			this.dots.forEach(function (d, k) {
				d.classList.toggle('is-active', k === cur);
				d.setAttribute('aria-current', k === cur ? 'true' : 'false');
			});

			if (this.thumbs.length) {
				this.thumbs.forEach(function (t) {
					t.classList.remove('is-visible');
					t.removeAttribute('data-k');
				});
				void this.thumbWrap.offsetWidth; // restart the entrance animation
				var show = Math.min(this.o.thumbs, n - 1);
				for (var k = 1; k <= show; k++) {
					var t = this.thumbs[(cur + k) % n];
					t.style.order = k;
					t.style.setProperty('--k', k - 1);
					t.setAttribute('data-k', k);
					t.classList.add('is-visible');
				}
			}
		}

		restartBar() {
			if (!this.bar) return;
			this.bar.classList.remove('is-running');
			void this.bar.offsetWidth;
			this.bar.classList.add('is-running');
		}

		schedule(ms) {
			var self = this;
			clearTimeout(this.timer);
			this.startedAt = Date.now();
			this.remaining = ms;
			this.timer = setTimeout(function () {
				if (!self.root.isConnected) { self.destroy(); return; }
				self.next();
			}, ms);
		}

		pause(reason) { this.reasons.add(reason); this.update(); }
		resume(reason) { this.reasons.delete(reason); this.update(); }

		update() {
			if (this.destroyed) return;
			var run = this.reasons.size === 0;
			if (run === this.running) return;
			this.running = run;
			this.root.classList.toggle('is-paused', !run);
			if (this.live) this.live.setAttribute('aria-live', run ? 'off' : 'polite');
			if (run) {
				this.schedule(Math.max(this.remaining, 300));
			} else {
				clearTimeout(this.timer);
				this.remaining -= Date.now() - this.startedAt;
			}
		}

		// Editor: show a slide and hold it while its repeater item is open.
		hold(i) {
			if (this.destroyed || this.count < 2) return;
			this.pause('hold');
			this.go(i);
		}

		release() { if (!this.destroyed) this.resume('hold'); }

		destroy() {
			if (this.destroyed) return;
			this.destroyed = true;
			clearTimeout(this.timer);
			clearTimeout(this.prevTimer);
			if (this.io) this.io.disconnect();
			this.cleanup.forEach(function (fn) { fn(); });
			this.cleanup = [];
		}
	}

	function mount(el) {
		if (!el) return null;
		if (el._hsSlider) el._hsSlider.destroy();
		var root = el.querySelector('.hs-hero');
		el._hsSlider = root ? new HeroSlider(root) : null;
		return el._hsSlider;
	}

	function register() {
		var fe = window.elementorFrontend;
		var hook = 'frontend/element_ready/' + WIDGET + '.default';
		var Base = window.elementorModules && elementorModules.frontend && elementorModules.frontend.handlers && elementorModules.frontend.handlers.Base;

		if (fe.isEditMode() && Base) {
			class HeroSliderHandler extends Base {
				onInit() {
					super.onInit.apply(this, arguments);
					this.slider = mount(this.$element[0]);
					var idx = this.getEditSettings('activeItemIndex');
					if (this.slider && idx) this.slider.hold(idx - 1);
				}

				onEditSettingsChange(name, value) {
					if (name !== 'activeItemIndex' || !this.slider) return;
					value ? this.slider.hold(value - 1) : this.slider.release();
				}

				onDestroy() {
					if (this.slider) this.slider.destroy();
					if (super.onDestroy) super.onDestroy.apply(this, arguments);
				}
			}

			fe.hooks.addAction(hook, function ($el) {
				fe.elementsHandler.addHandler(HeroSliderHandler, { $element: $el });
			});
		} else {
			fe.hooks.addAction(hook, function ($el) { mount($el[0]); });
		}
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		register();
		document.querySelectorAll('.elementor-widget-' + WIDGET).forEach(mount);
	} else if (window.jQuery) {
		window.jQuery(window).on('elementor/frontend/init', register);
	} else {
		document.addEventListener('DOMContentLoaded', function () {
			document.querySelectorAll('.elementor-widget-' + WIDGET).forEach(mount);
		});
	}
})();
