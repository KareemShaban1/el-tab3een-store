<script type="text/javascript">
    $(document).ready(function () {
        var governorates_table = $('#lf_governorates_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action([\App\Http\Controllers\LocationsFees\GovernorateController::class, "index"]) }}',
            columnDefs: [{
                targets: -1,
                orderable: false,
                searchable: false
            }]
        });

        var cities_table = $('#lf_cities_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action([\App\Http\Controllers\LocationsFees\CityController::class, "index"]) }}',
            columnDefs: [{
                targets: -1,
                orderable: false,
                searchable: false
            }],
            fnDrawCallback: function () {
                __currency_convert_recursively($('#lf_cities_table'));
            }
        });

        var areas_table = $('#lf_areas_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action([\App\Http\Controllers\LocationsFees\AreaController::class, "index"]) }}',
            columnDefs: [{
                targets: -1,
                orderable: false,
                searchable: false
            }],
            fnDrawCallback: function () {
                __currency_convert_recursively($('#lf_areas_table'));
            }
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
            governorates_table.columns.adjust();
            cities_table.columns.adjust();
            areas_table.columns.adjust();
        });

        function bindLfForm(formId, table) {
            $(document).on('submit', formId, function (e) {
                e.preventDefault();
                var form = $(this);
                $.ajax({
                    method: form.attr('method') || 'POST',
                    url: form.attr('action'),
                    dataType: 'json',
                    data: form.serialize(),
                    success: function (result) {
                        if (result.success) {
                            $('div.view_modal').modal('hide');
                            toastr.success(result.msg);
                            table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });
        }

        bindLfForm('#lf_governorate_form', governorates_table);
        bindLfForm('#lf_city_form', cities_table);
        bindLfForm('#lf_area_form', areas_table);

        $(document).on('click', '.delete_governorate_button', function (e) {
            e.preventDefault();
            var href = $(this).data('href');
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function (willDelete) {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function (result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                governorates_table.ajax.reload();
                                cities_table.ajax.reload();
                                areas_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        $(document).on('click', '.delete_city_button', function (e) {
            e.preventDefault();
            var href = $(this).data('href');
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function (willDelete) {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function (result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                cities_table.ajax.reload();
                                areas_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        $(document).on('click', '.delete_area_button', function (e) {
            e.preventDefault();
            var href = $(this).data('href');
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function (willDelete) {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function (result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                areas_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
