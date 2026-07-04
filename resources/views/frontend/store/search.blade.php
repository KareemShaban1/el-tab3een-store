@extends('frontend.store.theme_layout')

@section('content')
@php
    $initialQuery = trim((string) ($initialQuery ?? request('q', '')));
@endphp
<style>
    .search-page-wrap {
        padding: 24px 16px 100px;
        max-width: 720px;
        margin: 0 auto;
    }

    .search-page-title {
        margin: 0 0 6px;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--primary);
    }

    .search-page-sub {
        margin: 0 0 18px;
        color: var(--muted);
        font-size: .92rem;
    }

    .search-page-bar {
        display: flex;
        align-items: stretch;
        gap: 0;
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: var(--r-md);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .search-page-bar:focus-within {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(234, 84, 26, .12);
    }

    .search-page-input {
        flex: 1;
        border: none;
        outline: none;
        padding: 14px 16px;
        font-size: 1rem;
        font-family: inherit;
        background: transparent;
        min-width: 0;
    }

    .search-page-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        border: none;
        background: var(--accent);
        color: #fff;
        cursor: pointer;
        flex-shrink: 0;
    }

    .search-page-btn svg {
        width: 20px;
        height: 20px;
    }

    .search-page-hint {
        margin: 12px 4px 0;
        font-size: .85rem;
        color: var(--muted);
        min-height: 1.25rem;
    }

    .search-page-hint.is-loading {
        color: var(--accent);
    }

    .search-page-results {
        margin-top: 20px;
    }

    .search-page-group {
        margin-bottom: 22px;
    }

    .search-page-group-title {
        margin: 0 0 10px;
        font-size: .82rem;
        font-weight: 700;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .search-page-list {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .search-page-list a.store-search-item {
        padding: 14px 16px;
    }

    .search-page-empty {
        text-align: center;
        padding: 36px 16px;
        color: var(--muted);
        background: var(--bg-soft);
        border-radius: var(--r-md);
        font-size: .95rem;
    }

    .search-page-all-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        font-size: .88rem;
        font-weight: 700;
        color: var(--accent);
        text-decoration: none;
    }

    .search-page-all-link:hover {
        text-decoration: underline;
    }
</style>

<div class="search-page-wrap">
    <h1 class="search-page-title">بحث</h1>
    <p class="search-page-sub">ابحث عن منتج أو قسم في المتجر</p>

    <form class="search-page-bar" id="store-page-search-form" action="{{ route('store.search') }}" method="GET" role="search">
        <input
            type="search"
            name="q"
            id="store-page-search-q"
            class="search-page-input"
            value="{{ $initialQuery }}"
            placeholder="ابحث عن منتج، ماركة أو فئة..."
            autocomplete="off"
            autocapitalize="off"
            spellcheck="false"
            aria-label="بحث"
            autofocus>
        <button type="submit" class="search-page-btn" aria-label="بحث">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
            </svg>
        </button>
    </form>

    <p class="search-page-hint" id="store-page-search-hint">
        {{ mb_strlen($initialQuery) >= 3 ? '' : 'اكتب 3 أحرف على الأقل للبحث' }}
    </p>

    <div class="search-page-results" id="store-page-search-results" aria-live="polite"></div>
</div>
@endsection
