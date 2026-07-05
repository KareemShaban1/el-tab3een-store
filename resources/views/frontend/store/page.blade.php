@extends('frontend.store.theme_layout')

@section('content')
<section class="store-page-section">
	<div class="container">
		<article class="store-page-card">
			<header class="store-page-header">
				<h1 class="store-page-title">{{ $page->title }}</h1>
				@if (! empty($page->excerpt))
				<p class="store-page-excerpt">{{ $page->excerpt }}</p>
				@endif
			</header>
			<div class="store-page-content">
				{!! $page->content !!}
			</div>
		</article>
	</div>
</section>
@endsection

@push('styles')
<style>
.store-page-section {
	padding: 2rem 0 3rem;
}
.store-page-card {
	background: var(--card, #fff);
	border: 1px solid var(--border, #e8e8f0);
	border-radius: 16px;
	padding: 1.75rem 1.5rem;
	box-shadow: 0 8px 30px rgba(20, 20, 60, 0.06);
}
.store-page-title {
	font-size: 1.75rem;
	font-weight: 800;
	margin: 0 0 0.75rem;
	color: var(--text, #1a1a2e);
}
.store-page-excerpt {
	color: var(--muted, #6b6b8a);
	margin: 0 0 1.25rem;
	line-height: 1.7;
}
.store-page-content {
	color: var(--text, #1a1a2e);
	line-height: 1.9;
	font-size: 0.98rem;
}
.store-page-content h2,
.store-page-content h3,
.store-page-content h4 {
	margin: 1.5rem 0 0.75rem;
	font-weight: 700;
}
.store-page-content p,
.store-page-content ul,
.store-page-content ol {
	margin-bottom: 1rem;
}
.store-page-content ul,
.store-page-content ol {
	padding-right: 1.25rem;
}
.f-legal-links {
	display: flex;
	flex-wrap: wrap;
	gap: 0.75rem 1rem;
	justify-content: center;
	margin-top: 0.75rem;
}
.f-legal-link {
	color: rgba(255, 255, 255, 0.75);
	font-size: 0.85rem;
	text-decoration: none;
}
.f-legal-link:hover {
	color: #fff;
	text-decoration: underline;
}
@media (min-width: 768px) {
	.footer-bottom {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 1rem;
		flex-wrap: wrap;
	}
	.f-legal-links {
		margin-top: 0;
		justify-content: flex-end;
	}
}
</style>
@endpush
