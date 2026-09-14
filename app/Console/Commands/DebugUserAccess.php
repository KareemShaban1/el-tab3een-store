<?php

namespace App\Console\Commands;

use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Modules\Manufacturing\Support\PackagingFeature;

class DebugUserAccess extends Command
{
    protected $signature = 'user:debug-access {user_id : The users.id to inspect}';

    protected $description = 'Show user details, roles, permissions, subscription modules, and Manufacturing menu visibility';

    public function handle()
    {
        $userId = (int) $this->argument('user_id');

        $user = User::withTrashed()->with('business')->find($userId);
        if (! $user) {
            $this->error("User #{$userId} not found.");

            return 1;
        }

        // Simulate request auth so can() / ModuleUtil behave like the web app.
        Auth::login($user);

        $this->printUser($user);
        $this->printRolesAndPermissions($user);
        $this->printSubscription($user);
        $this->printManufacturingVerdict($user);

        Auth::logout();

        return 0;
    }

    protected function printUser(User $user): void
    {
        $this->info('========== USER ==========');
        $this->table(
            ['Field', 'Value'],
            [
                ['id', $user->id],
                ['username', $user->username],
                ['email', $user->email],
                ['user_type', $user->user_type],
                ['status', $user->status ?? '(null)'],
                ['allow_login', $user->allow_login ?? '(null)'],
                ['is_cmmsn_agnt', $user->is_cmmsn_agnt ?? '(null)'],
                ['business_id', $user->business_id],
                ['business_name', optional($user->business)->name],
                ['deleted_at', $user->deleted_at],
                ['created_at', $user->created_at],
            ]
        );
    }

    protected function printRolesAndPermissions(User $user): void
    {
        $this->info('========== ROLES ==========');
        $roles = $user->getRoleNames()->values()->all();
        $this->line(empty($roles) ? '(no roles)' : implode(', ', $roles));

        $this->info('========== DIRECT PERMISSIONS ==========');
        $direct = $user->getDirectPermissions()->pluck('name')->sort()->values()->all();
        $this->line(empty($direct) ? '(none)' : implode("\n", $direct));

        $this->info('========== ALL PERMISSIONS (roles + direct) ==========');
        $all = $user->getAllPermissions()->pluck('name')->sort()->values()->all();
        $this->line(empty($all) ? '(none)' : implode("\n", $all));

        $mfgPerms = [
            'superadmin',
            'manufacturing.access_recipe',
            'manufacturing.add_recipe',
            'manufacturing.edit_recipe',
            'manufacturing.access_production',
            'manufacturing.access_packaging',
            'manufacturing.manage_packaging_profiles',
        ];

        $this->info('========== MANUFACTURING / SUPERADMIN CAN() ==========');
        $rows = [];
        foreach ($mfgPerms as $perm) {
            $rows[] = [$perm, $user->can($perm) ? 'YES' : 'NO'];
        }
        $this->table(['Permission', 'can()'], $rows);
    }

    protected function printSubscription(User $user): void
    {
        $this->info('========== SUBSCRIPTION (business) ==========');

        $businessId = $user->business_id;
        if (empty($businessId)) {
            $this->warn('User has no business_id.');

            return;
        }

        $moduleUtil = new ModuleUtil();
        $superadminInstalled = $moduleUtil->isSuperadminInstalled();
        $this->line('Superadmin module installed: '.($superadminInstalled ? 'YES' : 'NO'));

        if (! $superadminInstalled) {
            $this->line('Without Superadmin, subscription gates are skipped (modules treated as allowed).');

            return;
        }

        if (! class_exists(\Modules\Superadmin\Entities\Subscription::class)) {
            $this->error('Subscription class not found.');

            return;
        }

        $active = \Modules\Superadmin\Entities\Subscription::active_subscription($businessId);
        if (empty($active)) {
            $this->error('NO ACTIVE APPROVED SUBSCRIPTION for business_id='.$businessId);
            $this->line('Checked: start_date <= today, end_date >= today, status=approved');

            $latest = \Modules\Superadmin\Entities\Subscription::where('business_id', $businessId)
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'package_id', 'status', 'start_date', 'end_date', 'created_at']);

            if ($latest->isNotEmpty()) {
                $this->warn('Latest subscriptions for this business:');
                $this->table(
                    ['id', 'package_id', 'status', 'start_date', 'end_date', 'created_at'],
                    $latest->map(fn ($s) => [
                        $s->id,
                        $s->package_id,
                        $s->status,
                        $s->start_date,
                        $s->end_date,
                        $s->created_at,
                    ])->all()
                );
            }

            return;
        }

        $packageName = optional($active->package)->name;
        $this->table(
            ['Field', 'Value'],
            [
                ['subscription_id', $active->id],
                ['package_id', $active->package_id],
                ['package_name', $packageName],
                ['status', $active->status],
                ['start_date', $active->start_date],
                ['end_date', $active->end_date],
            ]
        );

        $details = $active->package_details ?? [];
        if (! is_array($details)) {
            $details = [];
        }

        $this->info('--- package_details (modules / limits) ---');
        if (empty($details)) {
            $this->warn('(empty package_details)');
        } else {
            ksort($details);
            $rows = [];
            foreach ($details as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $rows[] = [$key, (string) $value];
            }
            $this->table(['Key', 'Value'], $rows);
        }

        $moduleKeys = array_filter(array_keys($details), function ($k) {
            return is_string($k) && substr($k, -7) === '_module';
        });
        sort($moduleKeys);

        $this->info('--- Enabled-looking modules (*_module) ---');
        $modRows = [];
        foreach ($moduleKeys as $key) {
            $val = $details[$key];
            $enabled = ! empty($val) && $val !== '0' && $val !== 0 && $val !== false;
            $modRows[] = [$key, var_export($val, true), $enabled ? 'ENABLED' : 'DISABLED'];
        }
        if (empty($modRows)) {
            $this->warn('No *_module keys in package_details.');
        } else {
            $this->table(['Module key', 'Raw', 'Status'], $modRows);
        }

        $mfgSub = $moduleUtil->hasThePermissionInSubscription($businessId, 'manufacturing_module', 'superadmin_package');
        $this->line('hasThePermissionInSubscription(manufacturing_module): '.($mfgSub ? 'YES' : 'NO'));
    }

    protected function printManufacturingVerdict(User $user): void
    {
        $this->info('========== MANUFACTURING MENU VERDICT ==========');

        $businessId = $user->business_id;
        $moduleUtil = new ModuleUtil();

        $isSuperadmin = $user->can('superadmin');
        $mfgInSub = $businessId
            ? (bool) $moduleUtil->hasThePermissionInSubscription($businessId, 'manufacturing_module', 'superadmin_package')
            : false;

        $isMfgEnabled = $isSuperadmin || $mfgInSub;
        $canRecipe = $user->can('manufacturing.access_recipe');
        $canProduction = $user->can('manufacturing.access_production');
        $canPackaging = PackagingFeature::userCanAccessPackaging();
        $packagingBiz = $businessId ? PackagingFeature::isEnabledForBusiness($businessId) : false;

        $parentMenu = $isMfgEnabled;
        $anyChild = $canRecipe || $canProduction;
        $wouldSeeUsefulMenu = $parentMenu && $anyChild;

        $this->table(
            ['Check', 'Result'],
            [
                ['user.can(superadmin)', $isSuperadmin ? 'YES' : 'NO'],
                ['subscription manufacturing_module', $mfgInSub ? 'YES' : 'NO'],
                ['$is_mfg_enabled (parent menu gate)', $isMfgEnabled ? 'YES → parent can be added' : 'NO → menu NOT added'],
                ['can(manufacturing.access_recipe)', $canRecipe ? 'YES' : 'NO'],
                ['can(manufacturing.access_production)', $canProduction ? 'YES' : 'NO'],
                ['packaging enabled for business', $packagingBiz ? 'YES' : 'NO'],
                ['userCanAccessPackaging()', $canPackaging ? 'YES' : 'NO'],
                ['Any submenu links?', $anyChild ? 'YES' : 'NO (empty dropdown)'],
                ['Would user see usable Manufacturing menu?', $wouldSeeUsefulMenu ? 'YES' : 'NO'],
            ]
        );

        if (! $wouldSeeUsefulMenu) {
            $this->warn('Fix hints:');
            if (! $isMfgEnabled) {
                $this->line('- Enable manufacturing_module on the business active subscription package (Superadmin → packages / subscription).');
            }
            if (! $canRecipe && ! $canProduction) {
                $this->line('- Assign role permissions: manufacturing.access_recipe and/or manufacturing.access_production, then have the user re-login.');
            }
        } else {
            $this->info('Gates look OK. If still missing in UI: clear permission cache, hard refresh, confirm same business_id session.');
            $this->line('Optional: php artisan permission:cache-reset');
        }
    }
}
