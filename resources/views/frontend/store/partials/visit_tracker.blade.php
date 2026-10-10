@php
	$visitToken = $websiteVisitToken ?? null;
@endphp
@if (!empty($visitToken))
<script>
(function() {
	const VISIT_TOKEN = @json($visitToken);
	const UPDATE_URL = @json(url('/store/visit-logs')) + '/' + encodeURIComponent(VISIT_TOKEN);
	const CSRF = @json(csrf_token());
	const startedAt = Date.now();
	let pendingEvents = [];
	let flushTimer = null;
	let lastSentSeconds = 0;

	function elapsedSeconds() {
		return Math.min(86400, Math.floor((Date.now() - startedAt) / 1000));
	}

	function trackEvent(type, label, meta) {
		pendingEvents.push({
			type: String(type || 'event').slice(0, 80),
			label: label ? String(label).slice(0, 255) : null,
			at: new Date().toISOString(),
			meta: meta && typeof meta === 'object' ? meta : null,
		});
		const urgent = type === 'filter_applied' || type === 'add_to_cart' || type === 'checkout_click';
		if (urgent || pendingEvents.length >= 8) {
			flush(false);
		} else {
			scheduleFlush();
		}
	}

	window.trackStoreVisitEvent = trackEvent;

	function scheduleFlush() {
		if (flushTimer) return;
		flushTimer = setTimeout(function() {
			flushTimer = null;
			flush(false);
		}, 5000);
	}

	function flush(useBeacon) {
		const seconds = elapsedSeconds();
		const events = pendingEvents.splice(0, pendingEvents.length);
		if (seconds === lastSentSeconds && events.length === 0) {
			return;
		}
		lastSentSeconds = seconds;

		const payload = {
			time_spent_seconds: seconds,
			page_title: document.title || null,
			events: events,
		};

		const productNameEl = document.querySelector('[data-product-name], .product-title, h1');
		if (productNameEl && productNameEl.textContent) {
			payload.product_name = productNameEl.textContent.trim().slice(0, 255);
		}

		if (useBeacon && navigator.sendBeacon) {
			const form = new FormData();
			form.append('_token', CSRF);
			form.append('time_spent_seconds', String(payload.time_spent_seconds));
			if (payload.page_title) form.append('page_title', payload.page_title);
			if (payload.product_name) form.append('product_name', payload.product_name);
			events.forEach(function(event, index) {
				form.append('events[' + index + '][type]', event.type || 'event');
				if (event.label) form.append('events[' + index + '][label]', event.label);
				if (event.at) form.append('events[' + index + '][at]', event.at);
				if (event.meta && typeof event.meta === 'object') {
					Object.keys(event.meta).forEach(function(key) {
						const value = event.meta[key];
						if (value === null || value === undefined) return;
						form.append('events[' + index + '][meta][' + key + ']', String(value));
					});
				}
			});
			navigator.sendBeacon(UPDATE_URL, form);
			return;
		}

		try {
			fetch(UPDATE_URL, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json',
					'X-CSRF-TOKEN': CSRF,
					'X-Requested-With': 'XMLHttpRequest',
				},
				credentials: 'same-origin',
				keepalive: true,
				body: JSON.stringify(payload),
			}).catch(function() {});
		} catch (e) {}
	}

	document.addEventListener('click', function(e) {
		const target = e.target.closest(
			'[data-add], .pa-wish, [data-quickview], .cd-checkout, .cart-open, .h-cart, a, button, [data-wish]'
		);
		if (!target) return;

		if (target.matches('[data-add]') || target.closest('[data-add]')) {
			// Handled by wrapped window.addToCart to avoid duplicate events.
			return;
		}

		if (target.matches('.pa-wish, [data-wish]') || target.closest('.pa-wish, [data-wish]')) {
			const btn = target.closest('.pa-wish, [data-wish]') || target;
			trackEvent('wishlist_toggle', 'wishlist', {
				id: btn.dataset.wish || null,
			});
			return;
		}

		if (target.matches('[data-quickview]') || target.closest('[data-quickview]')) {
			const btn = target.closest('[data-quickview]') || target;
			trackEvent('quick_view', 'quick_view', {
				id: btn.dataset.quickview || null,
			});
			return;
		}

		if (target.matches('.cd-checkout') || target.closest('.cd-checkout')) {
			trackEvent('checkout_click', 'checkout');
			return;
		}

		if (target.matches('.cart-open, .h-cart') || target.closest('.cart-open, .h-cart')) {
			trackEvent('open_cart', 'cart');
		}
	}, true);

	document.addEventListener('submit', function(e) {
		const form = e.target;
		if (!form || form.tagName !== 'FORM') return;
		trackEvent('form_submit', form.getAttribute('action') || form.id || 'form');
	}, true);

	if (typeof window.addToCart === 'function') {
		const originalAddToCart = window.addToCart;
		window.addToCart = function(id, name, price, img, variationId, source) {
			trackEvent('add_to_cart', name || String(id), {
				id: id,
				variation_id: variationId || null,
				source: source || null,
				price: price || null,
			});
			return originalAddToCart.apply(this, arguments);
		};
	}

	setInterval(function() {
		flush(false);
	}, 15000);

	window.addEventListener('pagehide', function() {
		flush(true);
	});
	window.addEventListener('beforeunload', function() {
		flush(true);
	});
	document.addEventListener('visibilitychange', function() {
		if (document.visibilityState === 'hidden') {
			flush(true);
		}
	});

	let browserLocationSent = false;

	function postLocationPayload(payload) {
		if (browserLocationSent) return;
		browserLocationSent = true;
		payload.time_spent_seconds = elapsedSeconds();
		try {
			fetch(UPDATE_URL, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json',
					'X-CSRF-TOKEN': CSRF,
					'X-Requested-With': 'XMLHttpRequest',
				},
				credentials: 'same-origin',
				keepalive: true,
				body: JSON.stringify(payload),
			}).catch(function() {
				browserLocationSent = false;
			});
		} catch (e) {
			browserLocationSent = false;
		}
	}

	async function reverseGeocode(lat, lon) {
		try {
			const url = 'https://api.bigdatacloud.net/data/reverse-geocode-client'
				+ '?latitude=' + encodeURIComponent(lat)
				+ '&longitude=' + encodeURIComponent(lon)
				+ '&localityLanguage=ar';
			const res = await fetch(url);
			if (!res.ok) return null;
			return await res.json();
		} catch (e) {
			return null;
		}
	}

	async function sendBrowserLocation(position) {
		if (!position || !position.coords) return;
		const lat = position.coords.latitude;
		const lon = position.coords.longitude;
		const accuracy = position.coords.accuracy;
		if (typeof lat !== 'number' || typeof lon !== 'number') return;

		const payload = {
			latitude: lat,
			longitude: lon,
			location_accuracy: accuracy || null,
		};

		const place = await reverseGeocode(lat, lon);
		if (place) {
			payload.browser_city = place.city || place.locality || place.localityInfo?.administrative?.[3]?.name || null;
			payload.browser_region = place.principalSubdivision || place.localityInfo?.administrative?.[1]?.name || null;
			payload.browser_country = place.countryName || null;
			payload.browser_country_code = place.countryCode || null;
		}

		postLocationPayload(payload);
	}

	function requestBrowserLocation() {
		if (!navigator.geolocation) return;
		navigator.geolocation.getCurrentPosition(
			function(pos) { sendBrowserLocation(pos); },
			function() {
				// Retry once with lower accuracy if first attempt fails.
				navigator.geolocation.getCurrentPosition(
					function(pos) { sendBrowserLocation(pos); },
					function() {},
					{ enableHighAccuracy: false, timeout: 10000, maximumAge: 600000 }
				);
			},
			{
				enableHighAccuracy: true,
				timeout: 15000,
				maximumAge: 60000,
			}
		);
	}

	// Initial ping so page title / product name are captured quickly.
	setTimeout(function() {
		flush(false);
		requestBrowserLocation();
	}, 800);

	// Also request location on first user interaction (helps some browsers).
	['click', 'touchstart', 'scroll'].forEach(function(evt) {
		window.addEventListener(evt, function once() {
			requestBrowserLocation();
			window.removeEventListener(evt, once, true);
		}, true);
	});
})();
</script>
@endif
