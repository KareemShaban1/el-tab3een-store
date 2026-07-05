<?php

namespace App\LocationsFees;

class Permissions
{
    public static function forRoleForm(): array
    {
        return [
            [
                'value' => 'locations_fees.access',
                'label' => __('locations_fees.access_locations_fees'),
                'default' => false,
            ],
            [
                'value' => 'locations_fees.create',
                'label' => __('locations_fees.add_location'),
                'default' => false,
            ],
            [
                'value' => 'locations_fees.update',
                'label' => __('locations_fees.edit_location'),
                'default' => false,
            ],
            [
                'value' => 'locations_fees.delete',
                'label' => __('locations_fees.delete_location'),
                'default' => false,
            ],
        ];
    }
}
