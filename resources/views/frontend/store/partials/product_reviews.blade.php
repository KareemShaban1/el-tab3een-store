@once
<style>
    .product-rating-summary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
        color: #374151;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
    }

    .product-rating-summary:hover {
        color: var(--accent, #ea541a);
    }

    .review-stars-display {
        color: #d1d5db;
        letter-spacing: 1px;
        direction: ltr;
        unicode-bidi: isolate;
    }

    .review-stars-display .is-on {
        color: #f59e0b;
    }

    .product-reviews {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 18px;
        display: grid;
        gap: 16px;
    }

    .product-reviews-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .product-reviews-title {
        margin: 0;
        font-size: 1.2rem;
    }

    .product-reviews-score {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
    }

    .product-reviews-count {
        color: #6b7280;
        font-weight: 600;
        font-size: 13px;
    }

    .review-list {
        display: grid;
        gap: 12px;
    }

    .review-item {
        border-top: 1px solid #f1f5f9;
        padding-top: 12px;
    }

    .review-meta {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        font-size: 13px;
        color: #6b7280;
    }

    .review-author {
        font-weight: 800;
        color: #111827;
    }

    .review-you {
        background: #fff7ed;
        color: #c2410c;
        border-radius: 999px;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: 700;
        margin-inline-start: 6px;
    }

    .review-comment {
        margin: 8px 0 0;
        line-height: 1.7;
        white-space: pre-wrap;
    }

    .review-empty {
        margin: 0;
        color: #6b7280;
    }

    .review-form {
        display: grid;
        gap: 10px;
        border-top: 1px solid #f1f5f9;
        padding-top: 14px;
    }

    .review-form h3 {
        margin: 0;
        font-size: 1rem;
    }

    .review-stars-input {
        display: flex;
        gap: 4px;
        direction: ltr;
        unicode-bidi: isolate;
    }

    .review-star {
        position: relative;
        cursor: pointer;
        font-size: 30px;
        line-height: 1;
        color: #d1d5db;
    }

    .review-star input {
        position: absolute;
        opacity: 0;
        width: 1px;
        height: 1px;
    }

    .review-star.is-on,
    .review-star:has(input:checked) {
        color: #f59e0b;
    }

    .review-star:focus-within {
        outline: 2px solid #fdba74;
        border-radius: 4px;
    }

    .review-form textarea {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        font: inherit;
        resize: vertical;
        min-height: 110px;
        background: #fff;
    }

    .review-submit {
        justify-self: start;
        background: var(--accent, #ea541a);
        color: #fff;
        border: 0;
        border-radius: 10px;
        padding: 12px 18px;
        font-weight: 800;
        cursor: pointer;
    }

    .review-login {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--accent, #ea541a);
        color: #fff;
        text-decoration: none;
        border-radius: 10px;
        padding: 12px 18px;
        font-weight: 800;
    }

    .review-error {
        color: #b91c1c;
        font-size: 13px;
        margin: 0;
    }

    .review-note,
    .review-success {
        border-radius: 10px;
        padding: 10px 12px;
        margin: 0;
    }

    .review-note {
        background: #fffbeb;
        color: #92400e;
    }

    .review-success {
        background: #ecfdf5;
        color: #065f46;
    }

    .review-sr {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }
</style>
@endonce

@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $reviewSummary = $reviewSummary ?? ['average' => 0, 'count' => 0];
    $productReviews = $productReviews ?? [];
    $customerReview = $customerReview ?? null;
    $placement = $placement ?? 'section';
    $reviewCount = (int) ($reviewSummary['count'] ?? 0);
    $reviewAverage = (float) ($reviewSummary['average'] ?? 0);
    $reviewProductId = (int) ($product['id'] ?? 0);
    $filledSummary = (int) round($reviewAverage);
@endphp

@if(($placement ?? 'section') === 'summary')
    @if($reviewCount > 0)
        <a class="product-rating-summary" href="#product-reviews">
            <span class="review-stars-display" aria-hidden="true">
                @for ($i = 1; $i <= 5; $i++)
                    <span class="{{ $i <= $filledSummary ? 'is-on' : '' }}">★</span>
                @endfor
            </span>
            <span>{{ number_format($reviewAverage, 1) }}</span>
            <span class="product-reviews-count">{{ __('storefront.reviews.count', ['count' => $reviewCount]) }}</span>
        </a>
    @endif
@else
    <section class="product-reviews" id="product-reviews">
        <div class="product-reviews-head">
            <h2 class="product-reviews-title">{{ __('storefront.reviews.title') }}</h2>
            @if($reviewCount > 0)
                <div class="product-reviews-score">
                    <span class="review-stars-display" aria-hidden="true">
                        @for ($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $filledSummary ? 'is-on' : '' }}">★</span>
                        @endfor
                    </span>
                    <span>{{ number_format($reviewAverage, 1) }}</span>
                    <span class="product-reviews-count">{{ __('storefront.reviews.count', ['count' => $reviewCount]) }}</span>
                </div>
            @endif
        </div>

        @if(session('review_status'))
            <p class="review-success" role="status">{{ session('review_status') }}</p>
        @endif

        @if($reviewCount === 0)
            <p class="review-empty">{{ __('storefront.reviews.empty') }}</p>
        @else
            <div class="review-list">
                @foreach($productReviews as $review)
                    <article class="review-item">
                        <div class="review-meta">
                            <div>
                                <span class="review-author">{{ $review['name'] !== '' ? $review['name'] : __('storefront.reviews.customer') }}</span>
                                @if(! empty($customerReview) && (int) $customerReview['id'] === (int) $review['id'])
                                    <span class="review-you">{{ __('storefront.reviews.yours') }}</span>
                                @endif
                            </div>
                            <span>{{ $review['date'] }}</span>
                        </div>
                        <div class="review-stars-display" aria-label="{{ __('storefront.reviews.stars', ['count' => $review['rating']]) }}">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= (int) $review['rating'] ? 'is-on' : '' }}">★</span>
                            @endfor
                        </div>
                        <p class="review-comment">{{ $review['comment'] }}</p>
                    </article>
                @endforeach
            </div>
        @endif

        @auth('customer')
            <form class="review-form" method="POST" action="{{ route('store.products.reviews.store', ['id' => $reviewProductId]) }}">
                @csrf
                <h3>{{ $customerReview ? __('storefront.reviews.update') : __('storefront.reviews.write') }}</h3>

                @if(! empty($customerReview) && empty($customerReview['is_visible']))
                    <p class="review-note">{{ __('storefront.reviews.hidden_notice') }}</p>
                @endif

                <div>
                    <div class="review-stars-input" id="review-stars">
                        @for ($i = 1; $i <= 5; $i++)
                            <label class="review-star">
                                <input type="radio" name="rating" value="{{ $i }}" @checked((int) old('rating', $customerReview['rating'] ?? 0) === $i) required>
                                <span aria-hidden="true">★</span>
                                <span class="review-sr">{{ __('storefront.reviews.stars', ['count' => $i]) }}</span>
                            </label>
                        @endfor
                    </div>
                    @error('rating')
                        <p class="review-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="field-label" for="product-review-comment">{{ __('storefront.reviews.comment') }}</label>
                    <textarea id="product-review-comment" name="comment" minlength="3" maxlength="2000" required placeholder="{{ __('storefront.reviews.comment_placeholder') }}">{{ old('comment', $customerReview['comment'] ?? '') }}</textarea>
                    @error('comment')
                        <p class="review-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="review-submit">
                    {{ $customerReview ? __('storefront.reviews.save_update') : __('storefront.reviews.submit') }}
                </button>
            </form>
            <script>
                (function () {
                    var root = document.getElementById('review-stars');
                    if (!root) return;
                    var labels = Array.prototype.slice.call(root.querySelectorAll('.review-star'));

                    function paint(value) {
                        labels.forEach(function (label) {
                            var input = label.querySelector('input');
                            label.classList.toggle('is-on', Number(input.value) <= Number(value));
                        });
                    }

                    var checked = root.querySelector('input:checked');
                    paint(checked ? checked.value : 0);

                    labels.forEach(function (label) {
                        var input = label.querySelector('input');
                        input.addEventListener('change', function () {
                            paint(input.value);
                        });
                        label.addEventListener('mouseenter', function () {
                            paint(input.value);
                        });
                    });

                    root.addEventListener('mouseleave', function () {
                        var current = root.querySelector('input:checked');
                        paint(current ? current.value : 0);
                    });
                })();
            </script>
        @else
            <a class="review-login" href="{{ route('store.auth.login.form', ['redirect' => url()->current().'#product-reviews']) }}">
                {{ __('storefront.reviews.login_to_review') }}
            </a>
        @endauth

        @if($errors->has('rating') || $errors->has('comment'))
            <script>
                document.getElementById('product-reviews')?.scrollIntoView();
            </script>
        @endif
    </section>
@endif
