<?php

namespace App\Console\Commands;

use App\System;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Menu;
use Modules\Manufacturing\Support\PackagingFeature;
use Nwidart\Modules\Facades\Module;

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

        Auth::login($user);

        // Match web session shape used by menus / ModuleUtil.
        session()->put('user', [
            'id' => $user->id,
            'surname' => $user->surname,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'business_id' => $user->business_id,
            'language' => $user->language,
        ]);
        if ($user->business) {
            session()->put('business', $user->business);
        }

        $this->printUser($user);
        $this->printRolesAndPermissions($user);
        $this->printModuleInstallStatus();
        $this->printSubscription($user);
        $this->printManufacturingVerdict($user);
        $this->simulateManufacturingMenu($user);

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
                ['server_today', now()->toDateString()],
                ['app_timezone', config('app.timezone')],
            ]
        );
    }

    protected function printRolesAndPermissions(User $user): void
    {
        $this->info('========== ROLES ==========');
        $roles = $user->getRoleNames()->values()->all();
        $this->line(empty($roles) ? '(no roles)' : implode(', ', $roles));

        $isAdminRole = $user->hasRole('Admin#'.$user->business_id);
        $this->line('hasRole(Admin#'.$user->business_id.'): '.($isAdminRole ? 'YES' : 'NO'));
        if ($isAdminRole) {
            $this->warn('NOTE: Gate::before grants ALL abilities to Admin#business_id (except backup/superadmin/manage_modules).');
            $this->warn('That is why can(manufacturing.*) can be YES even when getAllPermissions() is almost empty.');
        }

        $this->info('========== DIRECT PERMISSIONS ==========');
        $direct = $user->getDirectPermissions()->pluck('name')->sort()->values()->all();
        $this->line(empty($direct) ? '(none)' : implode("\n", $direct));

        $this->info('========== ALL PERMISSIONS (roles + direct) ==========');
        $all = $user->getAllPermissions()->pluck('name')->sort()->values()->all();
        $this->line(empty($all) ? '(none)' : implode("\n", $all));

        // DB role permission count (truth without Gate::before)
        foreach ($roles as $roleName) {
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }
            $count = $role->permissions()->count();
            $mfgCount = $role->permissions()->where('name', 'like', 'manufacturing.%')->count();
            $this->line("Role DB permissions for {$roleName}: total={$count}, manufacturing.*= {$mfgCount}");
        }

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

    protected function printModuleInstallStatus(): void
    {
        $this->info('========== MANUFACTURING MODULE INSTALL STATUS ==========');
        $this->line('THIS is what actually controls whether modifyAdminMenu() is called.');

        $moduleUtil = new ModuleUtil();
        $hasFiles = class_exists(Module::class) ? Module::has('Manufacturing') : null;
        $statusesPath = base_path('modules_statuses.json');
        $statusEnabled = null;
        if (is_file($statusesPath)) {
            $statuses = json_decode(file_get_contents($statusesPath), true) ?: [];
            $statusEnabled = array_key_exists('Manufacturing', $statuses) ? (bool) $statuses['Manufacturing'] : null;
        }
        $version = null;
        try {
            $version = System::getProperty('manufacturing_version');
        } catch (\Throwable $e) {
            $version = 'ERROR: '.$e->getMessage();
        }
        $installed = $moduleUtil->isModuleInstalled('Manufacturing');

        $this->table(
            ['Check', 'Result'],
            [
                ['Module::has(Manufacturing) files', $hasFiles === null ? 'n/a' : ($hasFiles ? 'YES' : 'NO')],
                ['modules_statuses.json Manufacturing', $statusEnabled === null ? 'missing key' : ($statusEnabled ? 'true' : 'false')],
                ['system.manufacturing_version', $version === null || $version === '' ? 'MISSING' : (string) $version],
                ['isModuleInstalled(Manufacturing)', $installed ? 'YES' : 'NO ← menu will NOT be registered'],
            ]
        );

        if (! $installed) {
            $this->error('Manufacturing is NOT installed according to ModuleUtil.');
            $this->line('Fix: open /manufacturing/install (as platform admin) or run module install so system.manufacturing_version is set.');
            $this->line('Or insert/update: INSERT INTO system (`key`, `value`) VALUES (\'manufacturing_version\', \'2.1\') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);');
            $this->line('(Then run manufacturing migrations / update if needed.)');
        }

        // Show related system keys
        try {
            $keys = DB::table('system')
                ->where('key', 'like', '%manufacturing%')
                ->orWhere('key', 'like', '%_version')
                ->orderBy('key')
                ->get(['key', 'value']);
            if ($keys->isNotEmpty()) {
                $this->info('--- system table version-like keys ---');
                $this->table(
                    ['key', 'value'],
                    $keys->map(fn ($r) => [$r->key, $r->value])->all()
                );
            }
        } catch (\Throwable $e) {
            $this->warn('Could not read system table: '.$e->getMessage());
        }
    }

    protected function printSubscription(User $user): void
    {
        $this->info('========== SUBSCRIPTION (business) ==========');

        $businessId = $user->business_id;
        if (empty($businessId)) {
            $this->warn('User has no business_id.');

            return;
        }

        $businessTz = optional($user->business)->time_zone ?: config('app.timezone');
        $appTz = config('app.timezone');
        $todayApp = now($appTz)->toDateString();
        $todayBusiness = now($businessTz)->toDateString();

        $this->table(
            ['Field', 'Value'],
            [
                ['app.timezone (CLI default)', $appTz],
                ['business.time_zone (used in web middleware)', $businessTz],
                ['today in app TZ', $todayApp],
                ['today in business TZ (WEB)', $todayBusiness],
            ]
        );

        if ($todayApp !== $todayBusiness) {
            $this->error('DATE MISMATCH: CLI and browser can disagree on whether a subscription is active.');
            $this->warn('AdminSidebarMenu runs AFTER Timezone middleware → uses business.time_zone.');
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

        // Show active under BOTH timezones (this is the usual CLI-vs-web trap).
        foreach ([
            'app TZ' => $appTz,
            'business TZ (web)' => $businessTz,
        ] as $label => $tz) {
            $today = now($tz)->toDateString();
            $active = \Modules\Superadmin\Entities\Subscription::where('business_id', $businessId)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->approved()
                ->first();

            $this->line("Active subscription under {$label} (today={$today}): ".($active ? 'YES id='.$active->id : 'NO'));
        }

        // Restore default Carbon timezone for the rest of the command.
        date_default_timezone_set($appTz);
        config(['app.timezone' => $appTz]);

        $active = \Modules\Superadmin\Entities\Subscription::active_subscription($businessId);
        if (empty($active)) {
            $this->error('NO ACTIVE APPROVED SUBSCRIPTION for business_id='.$businessId.' under current PHP timezone '.config('app.timezone'));
            $this->line('Checked: start_date <= today ('.now()->toDateString().'), end_date >= today, status=approved');

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

        $mfgSub = $moduleUtil->hasThePermissionInSubscription($businessId, 'manufacturing_module');
        $this->line('hasThePermissionInSubscription(manufacturing_module): '.($mfgSub ? 'YES' : 'NO'));
    }

    protected function printManufacturingVerdict(User $user): void
    {
        $this->info('========== MANUFACTURING MENU VERDICT ==========');

        $businessId = $user->business_id;
        $moduleUtil = new ModuleUtil();

        $isSuperadmin = $user->can('superadmin');
        $mfgInSub = $businessId
            ? (bool) $moduleUtil->hasThePermissionInSubscription($businessId, 'manufacturing_module')
            : false;
        $moduleInstalled = $moduleUtil->isModuleInstalled('Manufacturing');

        $isMfgEnabled = $isSuperadmin || $mfgInSub;
        $canRecipe = $user->can('manufacturing.access_recipe');
        $canProduction = $user->can('manufacturing.access_production');
        $canPackaging = PackagingFeature::userCanAccessPackaging();
        $packagingBiz = $businessId ? PackagingFeature::isEnabledForBusiness($businessId) : false;

        $parentMenu = $isMfgEnabled;
        $anyChild = $canRecipe || $canProduction;
        $wouldSeeUsefulMenu = $moduleInstalled && $parentMenu && $anyChild;

        $this->table(
            ['Check', 'Result'],
            [
                ['isModuleInstalled(Manufacturing)', $moduleInstalled ? 'YES' : 'NO ← CRITICAL'],
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
            if (! $moduleInstalled) {
                $this->line('1) Install Manufacturing module so system.manufacturing_version exists (menus are skipped until then).');
            }
            if (! $isMfgEnabled) {
                $this->line('2) Enable manufacturing_module on the business active subscription package.');
            }
            if (! $canRecipe && ! $canProduction) {
                $this->line('3) Assign manufacturing.access_recipe and/or manufacturing.access_production (or use Admin role).');
            }
        }
    }

    protected function simulateManufacturingMenu(User $user): void
    {
        $this->info('========== SIMULATE FULL SIDEBAR MODULE MENUS ==========');

        $moduleUtil = new ModuleUtil();
        if (! $moduleUtil->isModuleInstalled('Manufacturing')) {
            $this->error('Skipped: Manufacturing not installed → getModuleData("modifyAdminMenu") will never call it.');

            return;
        }

        try {
            Menu::create('admin-sidebar-menu', function ($menu) {
                $menu->url('#', 'HOME_PLACEHOLDER', ['icon' => ''])->order(5);
                $menu->url('#', 'PRODUCTS_PLACEHOLDER', ['icon' => ''])->order(20);
                $menu->url('#', 'PURCHASES_PLACEHOLDER', ['icon' => ''])->order(25);
            });

            // Same call path as AdminSidebarMenu middleware (all modules).
            $moduleUtil->getModuleData('modifyAdminMenu');

            $builder = Menu::instance('admin-sidebar-menu');
            $titles = [];
            if ($builder && method_exists($builder, 'getItems')) {
                foreach ($builder->getItems() as $item) {
                    $childCount = count($item->getChilds());
                    $titles[] = trim($item->title).($childCount ? " (children: {$childCount})" : '');
                }
            }

            $this->line('Menu titles after full getModuleData(modifyAdminMenu):');
            if (empty($titles)) {
                $this->warn('(could not list items)');
            } else {
                foreach ($titles as $t) {
                    $this->line(' - '.$t);
                }
            }

            $mfgTitle = __('manufacturing::lang.manufacturing');
            $found = collect($titles)->contains(function ($t) use ($mfgTitle) {
                return strpos($t, (string) $mfgTitle) !== false
                    || stripos($t, 'manufactur') !== false
                    || strpos($t, 'تصنيع') !== false;
            });
            $this->line('Manufacturing/تصنيع title present: '.($found ? 'YES' : 'NO'));
            $this->line('Arabic label to look for in UI: تصنيع');
            $this->line('Direct URLs to test while logged in as this user:');
            $this->line('  /manufacturing/recipe');
            $this->line('  /manufacturing/production');
            $this->line('If URLs work but sidebar missing: check laravel.log for ModuleUtil::getModuleData failed');
        } catch (\Throwable $e) {
            $this->error('Full menu simulation threw: '.$e->getMessage());
            $this->line($e->getFile().':'.$e->getLine());
        }
    }
}
