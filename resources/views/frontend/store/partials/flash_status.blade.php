@once
<style>
    .store-flash {
        padding: 16px 18px;
        margin-bottom: 16px;
        border-radius: 12px;
        border: 1px solid transparent;
    }
    .store-flash--success { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
    .store-flash--warning { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
    .store-flash--error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .store-flash__title { margin: 0 0 8px; font-weight: 800; }
    .store-flash__list { margin: 0; padding-inline-start: 1.15rem; line-height: 1.6; }
</style>
@endonce

@if (session('status'))
    @php
        $flash = session('status');
        $isPartial = ! empty($flash['partial']);
        $isSuccess = ! empty($flash['success']);
    @endphp
    <div class="store-flash store-flash--{{ $isPartial ? 'warning' : ($isSuccess ? 'success' : 'error') }}" role="alert" aria-live="polite">
        <p class="store-flash__title">{{ $flash['msg'] ?? '' }}</p>
        @if (! empty($flash['warnings']) && is_array($flash['warnings']))
            <ul class="store-flash__list">
                @foreach ($flash['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
