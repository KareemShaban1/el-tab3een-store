@extends('layouts.app')

@section('title', __('product_reviews.product_reviews'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('product_reviews.product_reviews')
    </h1>
    <p class="text-muted" style="margin-top:8px;">@lang('product_reviews.help')</p>
</section>

<section class="content">
    <div class="row" style="margin-bottom:15px;">
        <div class="col-md-4">
            <div class="tw-bg-white tw-rounded-xl tw-shadow-sm tw-ring-1 tw-ring-gray-200 tw-p-4">
                <div class="tw-text-sm tw-text-gray-500">@lang('product_reviews.total')</div>
                <div class="tw-text-2xl tw-font-bold">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="tw-bg-white tw-rounded-xl tw-shadow-sm tw-ring-1 tw-ring-gray-200 tw-p-4">
                <div class="tw-text-sm tw-text-gray-500">@lang('product_reviews.visible_count')</div>
                <div class="tw-text-2xl tw-font-bold">{{ $stats['visible'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="tw-bg-white tw-rounded-xl tw-shadow-sm tw-ring-1 tw-ring-gray-200 tw-p-4">
                <div class="tw-text-sm tw-text-gray-500">@lang('product_reviews.hidden_count')</div>
                <div class="tw-text-2xl tw-font-bold">{{ $stats['hidden'] }}</div>
            </div>
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row" style="margin-bottom:12px;">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="product_review_visibility">@lang('product_reviews.status')</label>
                    <select id="product_review_visibility" class="form-control">
                        <option value="">@lang('product_reviews.all')</option>
                        <option value="visible">@lang('product_reviews.visible')</option>
                        <option value="hidden">@lang('product_reviews.hidden')</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="product_review_rating">@lang('product_reviews.rating')</label>
                    <select id="product_review_rating" class="form-control">
                        <option value="">@lang('product_reviews.all')</option>
                        @for ($star = 5; $star >= 1; $star--)
                            <option value="{{ $star }}">{{ $star }} ★</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="product_review_search">@lang('product_reviews.search')</label>
                    <input type="text" id="product_review_search" class="form-control" placeholder="@lang('product_reviews.search_placeholder')">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="product_reviews_table" style="width:100%;">
                <thead>
                    <tr>
                        <th>@lang('product_reviews.date')</th>
                        <th>@lang('product_reviews.product')</th>
                        <th>@lang('product_reviews.customer')</th>
                        <th>@lang('product_reviews.rating')</th>
                        <th>@lang('product_reviews.comment')</th>
                        <th>@lang('product_reviews.status')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script type="text/javascript">
$(function () {
    var product_reviews_table = $('#product_reviews_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('product-reviews.index') }}',
            data: function (d) {
                d.visibility = $('#product_review_visibility').val();
                d.rating = $('#product_review_rating').val();
                d.q = $('#product_review_search').val();
            }
        },
        columns: [
            { data: 'created_at', name: 'product_reviews.created_at' },
            { data: 'product_name', name: 'products.name' },
            { data: 'customer', name: 'contacts.name', orderable: false },
            { data: 'rating', name: 'product_reviews.rating' },
            { data: 'comment', name: 'product_reviews.comment' },
            { data: 'is_visible', name: 'product_reviews.is_visible' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    $('#product_review_visibility, #product_review_rating').on('change', function () {
        product_reviews_table.ajax.reload();
    });

    var searchTimer;
    $('#product_review_search').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            product_reviews_table.ajax.reload();
        }, 350);
    });

    $(document).on('click', '.toggle_product_review', function (e) {
        e.preventDefault();
        $.ajax({
            method: 'POST',
            url: $(this).data('href'),
            dataType: 'json',
            success: function (result) {
                if (result.success) {
                    toastr.success(result.msg);
                    product_reviews_table.ajax.reload(null, false);
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function () {
                toastr.error(LANG.something_went_wrong);
            }
        });
    });

    $(document).on('click', '.delete_product_review', function (e) {
        e.preventDefault();
        var href = $(this).data('href');
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(function (willDelete) {
            if (!willDelete) {
                return;
            }
            $.ajax({
                method: 'DELETE',
                url: href,
                dataType: 'json',
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        product_reviews_table.ajax.reload(null, false);
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function () {
                    toastr.error(LANG.something_went_wrong);
                }
            });
        });
    });
});
</script>
@endsection
