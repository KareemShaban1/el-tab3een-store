@php
    $storefront_role_permissions = $role_permissions ?? [];
@endphp
<hr>
<div class="row check_group">
    <div class="col-md-1">
        <h4>@lang('role.storefront')</h4>
    </div>
    <div class="col-md-2">
        <div class="checkbox">
            <label>
                <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
            </label>
        </div>
    </div>
    <div class="col-md-9">
        @foreach ([
            'locations_fees.access',
            'tab3een_orders.view',
            'servo_orders.view',
            'hero_banners.access',
            'store_pages.access',
            'storefront_appearance.access',
            'storefront_whatsapp.access',
        ] as $storefront_permission)
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('permissions[]', $storefront_permission, in_array($storefront_permission, $storefront_role_permissions, true), ['class' => 'input-icheck']) !!}
                        {{ __('role.'.$storefront_permission) }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>
</div>
