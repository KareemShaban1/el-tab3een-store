<!-- Left side column. contains the logo and sidebar -->
<aside class="side-bar tw-relative tw-hidden tw-h-full tw-bg-white tw-w-64 xl:tw-w-64 lg:tw-flex lg:tw-flex-col tw-shrink-0">

    <!-- sidebar: style can be found in sidebar.less -->

    {{-- <a href="{{route('home')}}" class="logo">
		<span class="logo-lg">{{ Session::get('business.name') }}</span>
	</a> --}}

    <a href="{{route('home')}}"
        class="tw-flex tw-items-center tw-justify-center tw-w-full tw-border-r tw-h-15 tw-bg-@if(!empty(session('business.theme_color'))){{session('business.theme_color')}}@else{{'primary'}}@endif-800 tw-shrink-0 tw-border-primary-500/30">
        <p class="tw-text-lg tw-font-medium tw-text-white side-bar-heading tw-text-center">
            {{ Session::get('business.name') }} <span class="tw-inline-block tw-w-3 tw-h-3 tw-bg-green-400 tw-rounded-full" title="Online"></span>
        </p>
    </a>

    <!-- Sidebar Menu -->
    @if(!empty($__admin_sidebar_menu_titles) && (config('app.debug') || request()->boolean('debug_menu')))
        <!-- ADMIN_SIDEBAR_TITLES: {{ implode(' | ', $__admin_sidebar_menu_titles) }} -->
        <div class="tw-p-2 tw-text-xs tw-bg-yellow-100 tw-text-yellow-900 tw-border-b tw-border-yellow-300" style="white-space:normal;word-break:break-word;">
            <strong>Sidebar debug</strong><br>
            {{ implode(' | ', $__admin_sidebar_menu_titles) }}
        </div>
    @endif
    {!! Menu::render('admin-sidebar-menu', 'adminltecustom') !!}

    <!-- /.sidebar-menu -->
    <!-- /.sidebar -->
</aside>
