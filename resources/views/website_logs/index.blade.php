@extends('layouts.app')

@section('title', __('website_logs.website_logs'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fa fas fa-chart-line"></i> @lang('website_logs.website_logs')
    </h1>
</section>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('website_logs_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('website_logs_date_range', null, [
                    'placeholder' => __('lang_v1.select_a_date_range'),
                    'class' => 'form-control',
                    'id' => 'website_logs_date_range',
                    'readonly',
                ]) !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('visitor_type_filter', __('website_logs.visitor_type') . ':') !!}
                {!! Form::select('visitor_type_filter', [
                    '' => __('website_logs.all_visitors'),
                    'human' => __('website_logs.human'),
                    'bot' => __('website_logs.bot'),
                ], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'visitor_type_filter']) !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('page_type_filter', __('website_logs.page_type') . ':') !!}
                {!! Form::select('page_type_filter', ['' => __('lang_v1.all')] + $pageTypes, null, [
                    'class' => 'form-control select2',
                    'style' => 'width:100%',
                    'id' => 'page_type_filter',
                ]) !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('ip_filter', __('website_logs.ip') . ':') !!}
                {!! Form::text('ip_filter', null, ['class' => 'form-control', 'id' => 'ip_filter', 'placeholder' => __('website_logs.ip')]) !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('location_filter', __('website_logs.location') . ':') !!}
                {!! Form::text('location_filter', null, ['class' => 'form-control', 'id' => 'location_filter', 'placeholder' => __('website_logs.location_placeholder')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('page_filter', __('website_logs.page') . ':') !!}
                {!! Form::text('page_filter', null, ['class' => 'form-control', 'id' => 'page_filter', 'placeholder' => __('website_logs.page')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('product_filter', __('website_logs.product') . ':') !!}
                {!! Form::text('product_filter', null, ['class' => 'form-control', 'id' => 'product_filter', 'placeholder' => __('website_logs.product')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('referer_filter', __('website_logs.referer') . ':') !!}
                {!! Form::text('referer_filter', null, ['class' => 'form-control', 'id' => 'referer_filter', 'placeholder' => __('website_logs.referer')]) !!}
            </div>
        </div>
        <div class="col-md-3" style="padding-top: 25px;">
            <button type="button" class="btn btn-primary" id="website_logs_apply_filters">
                <i class="fa fa-filter"></i> @lang('report.filters')
            </button>
            <button type="button" class="btn btn-danger" id="website_logs_delete_filtered">
                <i class="fa fa-trash"></i> @lang('website_logs.delete_filtered')
            </button>
        </div>
    @endcomponent

    <div class="row" id="website_logs_stats">
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-aqua">
                <span class="info-box-icon"><i class="fa fa-eye"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('website_logs.total_visits')</span>
                    <span class="info-box-number" data-stat="total">{{ $stats['total'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="fa fa-user"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('website_logs.human_visits')</span>
                    <span class="info-box-number" data-stat="humans">{{ $stats['humans'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-yellow">
                <span class="info-box-icon"><i class="fa fa-robot"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('website_logs.bot_visits')</span>
                    <span class="info-box-number" data-stat="bots">{{ $stats['bots'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-purple">
                <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('website_logs.avg_time_spent')</span>
                    <span class="info-box-number" data-stat="avg_time_spent">{{ $stats['avg_time_spent'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('website_logs.most_visited_pages')])
                <div class="table-responsive">
                    <table class="table table-condensed" id="top_pages_table">
                        <thead>
                            <tr>
                                <th>@lang('website_logs.page')</th>
                                <th>@lang('website_logs.visits')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stats['top_pages'] as $row)
                                <tr>
                                    <td>{{ $row->page_path ?: '-' }}</td>
                                    <td>{{ $row->visits }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2">@lang('website_logs.no_data')</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('website_logs.most_visited_products')])
                <div class="table-responsive">
                    <table class="table table-condensed" id="top_products_table">
                        <thead>
                            <tr>
                                <th>@lang('website_logs.product')</th>
                                <th>@lang('website_logs.visits')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stats['top_products'] as $row)
                                <tr>
                                    <td>
                                        {{ $row->product_name ?: ('#'.$row->product_id) }}
                                        @if ($row->product_source)
                                            <small class="text-muted">({{ $row->product_source }})</small>
                                        @endif
                                    </td>
                                    <td>{{ $row->visits }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2">@lang('website_logs.no_data')</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('website_logs.top_referers')])
                <div class="table-responsive">
                    <table class="table table-condensed" id="top_referers_table">
                        <thead>
                            <tr>
                                <th>@lang('website_logs.referer')</th>
                                <th>@lang('website_logs.visits')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stats['top_referers'] as $row)
                                <tr>
                                    <td title="{{ $row->referer }}">{{ \Illuminate\Support\Str::limit($row->referer, 80) }}</td>
                                    <td>{{ $row->visits }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2">@lang('website_logs.no_data')</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('website_logs.visits_by_page_type')])
                <div class="table-responsive">
                    <table class="table table-condensed" id="by_type_table">
                        <thead>
                            <tr>
                                <th>@lang('website_logs.page_type')</th>
                                <th>@lang('website_logs.visits')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $types = $pageTypes; @endphp
                            @forelse ($stats['by_type'] as $row)
                                <tr>
                                    <td>{{ $types[$row->page_type] ?? ($row->page_type ?: '-') }}</td>
                                    <td>{{ $row->visits }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2">@lang('website_logs.no_data')</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('website_logs.visit_logs')])
        <table class="table table-bordered table-striped" id="website_logs_table" style="width:100%">
            <thead>
                <tr>
                    <th>@lang('website_logs.date_time')</th>
                    <th>@lang('website_logs.ip')</th>
                    <th>@lang('website_logs.location')</th>
                    <th>@lang('website_logs.page')</th>
                    <th>@lang('website_logs.page_type')</th>
                    <th>@lang('website_logs.visitor_type')</th>
                    <th>@lang('website_logs.time_spent')</th>
                    <th>@lang('website_logs.events')</th>
                    <th>@lang('website_logs.referer')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    var startDate = moment().subtract(29, 'days');
    var endDate = moment();
    var pageTypes = @json($pageTypes);

    if (typeof dateRangeSettings !== 'undefined') {
        dateRangeSettings.startDate = startDate;
        dateRangeSettings.endDate = endDate;
        $('#website_logs_date_range').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#website_logs_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                startDate = start;
                endDate = end;
                reloadAll();
            }
        );
        $('#website_logs_date_range').val(startDate.format(moment_date_format) + ' ~ ' + endDate.format(moment_date_format));
        $('#website_logs_date_range').on('cancel.daterangepicker', function() {
            $(this).val('');
            startDate = null;
            endDate = null;
            reloadAll();
        });
    }

    function filterPayload(d) {
        d = d || {};
        if (startDate) {
            d.start_date = startDate.format('YYYY-MM-DD');
        }
        if (endDate) {
            d.end_date = endDate.format('YYYY-MM-DD');
        }
        d.visitor_type = $('#visitor_type_filter').val();
        d.page_type = $('#page_type_filter').val();
        d.ip_address = $('#ip_filter').val();
        d.location = $('#location_filter').val();
        d.page_filter = $('#page_filter').val();
        d.product = $('#product_filter').val();
        d.referer = $('#referer_filter').val();
        return d;
    }

    function renderStatRows($tbody, rows, mapFn) {
        if (!rows || !rows.length) {
            $tbody.html('<tr><td colspan="2">@lang('website_logs.no_data')</td></tr>');
            return;
        }
        var html = '';
        rows.forEach(function(row) {
            html += mapFn(row);
        });
        $tbody.html(html);
    }

    function refreshStats() {
        $.ajax({
            url: '{{ action([\App\Http\Controllers\WebsiteVisitLogController::class, 'stats']) }}',
            data: filterPayload({}),
            dataType: 'json',
            success: function(result) {
                if (!result.success || !result.stats) {
                    return;
                }
                var s = result.stats;
                $('[data-stat="total"]').text(s.total);
                $('[data-stat="humans"]').text(s.humans);
                $('[data-stat="bots"]').text(s.bots);
                $('[data-stat="avg_time_spent"]').text(s.avg_time_spent);

                renderStatRows($('#top_pages_table tbody'), s.top_pages, function(row) {
                    return '<tr><td>' + $('<div>').text(row.page_path || '-').html() + '</td><td>' + row.visits + '</td></tr>';
                });
                renderStatRows($('#top_products_table tbody'), s.top_products, function(row) {
                    var name = row.product_name || ('#' + row.product_id);
                    var source = row.product_source ? ' <small class="text-muted">(' + $('<div>').text(row.product_source).html() + ')</small>' : '';
                    return '<tr><td>' + $('<div>').text(name).html() + source + '</td><td>' + row.visits + '</td></tr>';
                });
                renderStatRows($('#top_referers_table tbody'), s.top_referers, function(row) {
                    var ref = row.referer || '-';
                    var short = ref.length > 80 ? ref.substring(0, 80) + '...' : ref;
                    return '<tr><td title="' + $('<div>').text(ref).html() + '">' + $('<div>').text(short).html() + '</td><td>' + row.visits + '</td></tr>';
                });
                renderStatRows($('#by_type_table tbody'), s.by_type, function(row) {
                    var label = pageTypes[row.page_type] || row.page_type || '-';
                    return '<tr><td>' + $('<div>').text(label).html() + '</td><td>' + row.visits + '</td></tr>';
                });
            }
        });
    }

    var website_logs_table = $('#website_logs_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ action([\App\Http\Controllers\WebsiteVisitLogController::class, 'index']) }}',
            data: function(d) {
                filterPayload(d);
            }
        },
        columns: [
            { data: 'created_at', name: 'website_visit_logs.created_at' },
            { data: 'ip_address', name: 'website_visit_logs.ip_address' },
            { data: 'location', name: 'website_visit_logs.location_label', orderable: true, searchable: true },
            { data: 'page_path', name: 'website_visit_logs.page_path' },
            { data: 'page_type', name: 'website_visit_logs.page_type' },
            { data: 'visitor_type', name: 'website_visit_logs.is_bot', orderable: true, searchable: false },
            { data: 'time_spent_seconds', name: 'website_visit_logs.time_spent_seconds' },
            { data: 'events_preview', name: 'website_visit_logs.events_count', orderable: true, searchable: false },
            { data: 'referer', name: 'website_visit_logs.referer' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[0, 'desc']]
    });

    function reloadAll() {
        website_logs_table.ajax.reload();
        refreshStats();
    }

    $('#website_logs_apply_filters, #visitor_type_filter, #page_type_filter').on('change click', function(e) {
        if (e.type === 'click' && this.id !== 'website_logs_apply_filters') {
            return;
        }
        reloadAll();
    });

    var textFilterTimer;
    $('#ip_filter, #location_filter, #page_filter, #product_filter, #referer_filter').on('keyup', function() {
        clearTimeout(textFilterTimer);
        textFilterTimer = setTimeout(reloadAll, 400);
    });

    $(document).on('click', '.delete_website_log_button', function(e) {
        e.preventDefault();
        var href = $(this).data('href');
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(function(willDelete) {
            if (willDelete) {
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            reloadAll();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            }
        });
    });

    $('#website_logs_delete_filtered').on('click', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(function(willDelete) {
            if (willDelete) {
                $.ajax({
                    method: 'POST',
                    url: '{{ action([\App\Http\Controllers\WebsiteVisitLogController::class, 'bulkDestroy']) }}',
                    data: filterPayload({ _token: '{{ csrf_token() }}' }),
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            reloadAll();
                        } else {
                            toastr.error(result.msg || LANG.something_went_wrong);
                        }
                    }
                });
            }
        });
    });
});
</script>
@endsection
