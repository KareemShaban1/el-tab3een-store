<script type="text/javascript">
    $(document).ready( function() {

        function getTaxonomiesIndexPage () {
            var data = {category_type : $('#category_type').val()};
            $.ajax({
                method: "GET",
                dataType: "html",
                url: '/taxonomies-ajax-index-page',
                data: data,
                async: false,
                success: function(result){
                    $('.taxonomy_body').html(result);
                }
            });
        }

        function initializeTaxonomyDataTable() {
            //Category table
            if ($('#category_table').length) {
                var category_type = $('#category_type').val();
                var order_column_index = {{ $cat_code_enabled ? 2 : 1 }};
                category_table = $('#category_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    ajax: '/taxonomies?type=' + category_type,
                    aaSorting: [[order_column_index, 'asc']],
                    columns: [
                        { data: 'name', name: 'name', orderable: true, searchable: true },
                        @if($cat_code_enabled)
                            { data: 'short_code', name: 'short_code', orderable: false, searchable: true },
                        @endif
                        { data: 'order', name: 'order', orderable: true, searchable: false },
                        { data: 'active_in_app', name: 'active_in_app', orderable: false, searchable: false },
                        { data: 'description', name: 'description', orderable: false, searchable: true },
                        { data: 'action', name: 'action', orderable: false, searchable: false},
                    ],
                });
            }
        }

        @if(empty(request()->get('type')))
            getTaxonomiesIndexPage();
        @endif

        initializeTaxonomyDataTable();

        $(document).on('click', '.taxonomy-flag-toggle, .taxonomy-flag-toggle-wrap', function(e) {
            e.stopPropagation();
        });

        $(document).on('change', '.taxonomy-flag-toggle', function(e) {
            e.stopPropagation();
            var $cb = $(this);
            var previousChecked = !$cb.is(':checked');
            var url = $cb.data('url');
            if (!url) {
                return;
            }
            $cb.prop('disabled', true);
            $.ajax({
                method: 'POST',
                url: url,
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    value: $cb.is(':checked') ? 1 : 0
                },
                success: function(result) {
                    $cb.prop('disabled', false);
                    if (result.success == true) {
                        toastr.success(result.msg);
                    } else {
                        $cb.prop('checked', previousChecked);
                        toastr.error(result.msg);
                    }
                },
                error: function() {
                    $cb.prop('disabled', false);
                    $cb.prop('checked', previousChecked);
                    toastr.error(LANG.something_went_wrong);
                }
            });
        });
    });
    $(document).on('submit', 'form#category_add_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = new FormData(form[0]);

        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            dataType: 'json',
            data: data,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                __disable_submit_button(form.find('button[type="submit"]'));
            },
            success: function(result) {
                if (result.success === true) {
                    $('div.category_modal').modal('hide');
                    toastr.success(result.msg);
                    if(typeof category_table !== 'undefined') {
                        category_table.ajax.reload();
                    }

                    var evt = new CustomEvent("categoryAdded", {detail: result.data});
                    window.dispatchEvent(evt);

                    //event can be listened as
                    //window.addEventListener("categoryAdded", function(evt) {}
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    $(document).on('click', 'button.edit_category_button', function() {
        $('div.category_modal').load($(this).data('href'), function() {
            $(this).modal('show');

            $('form#category_edit_form').submit(function(e) {
                e.preventDefault();
                var form = $(this);
                var data = new FormData(form[0]);

                $.ajax({
                    method: 'POST',
                    url: form.attr('action'),
                    dataType: 'json',
                    data: data,
                    processData: false,
                    contentType: false,
                    beforeSend: function(xhr) {
                        __disable_submit_button(form.find('button[type="submit"]'));
                    },
                    success: function(result) {
                        if (result.success === true) {
                            $('div.category_modal').modal('hide');
                            toastr.success(result.msg);
                            category_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });
    });

    $(document).on('click', 'button.delete_category_button', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success === true) {
                            toastr.success(result.msg);
                            category_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
</script>