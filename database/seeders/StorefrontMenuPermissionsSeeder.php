<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class StorefrontMenuPermissionsSeeder extends Seeder
{
    /**
     * Permissions for the storefront sidebar links.
     *
     * Safe to re-run: uses firstOrCreate.
     *
     * php artisan db:seed --class=StorefrontMenuPermissionsSeeder
     */
    public function run()
    {
        $orderPermissions = [
            'tab3een_orders.view',
            'servo_orders.view',
        ];

        $storeSettingsPermissions = [
            'hero_banners.access',
            'store_pages.access',
            'storefront_appearance.access',
            'storefront_whatsapp.access',
        ];

        $permissions = array_merge(
            ['locations_fees.access'],
            $orderPermissions,
            $storeSettingsPermissions
        );

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $sellPermissions = [
            'sell.view',
            'direct_sell.view',
            'view_own_sell_only',
            'view_commission_agent_sell',
        ];

        $this->grantToRolesWithAny($orderPermissions, $sellPermissions);
        $this->grantToRolesWithAny($storeSettingsPermissions, ['business_settings.access']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if ($this->command) {
            $this->command->info('Storefront menu permissions seeded ('.count($permissions).').');
        }
    }

    /**
     * @param  array<int, string>  $grant
     * @param  array<int, string>  $whenRoleHas
     */
    private function grantToRolesWithAny(array $grant, array $whenRoleHas): void
    {
        $grantIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $grant)
            ->pluck('id');

        $roleIds = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->where('roles.guard_name', 'web')
            ->where('permissions.guard_name', 'web')
            ->whereIn('permissions.name', $whenRoleHas)
            ->distinct()
            ->pluck('roles.id');

        if ($grantIds->isEmpty() || $roleIds->isEmpty()) {
            return;
        }

        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($grantIds as $permissionId) {
                $rows[] = [
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('role_has_permissions')->insertOrIgnore($chunk);
        }
    }
}
