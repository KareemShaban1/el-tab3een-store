<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PaymentPermissionsSeeder extends Seeder
{
    /**
     * Seed payment permissions used by transaction payment screens
     * (sell, purchase, expense, hms, gym, payroll).
     *
     * Safe to re-run: uses firstOrCreate.
     *
     * php artisan db:seed --class=PaymentPermissionsSeeder
     */
    public function run()
    {
        $permissions = [
            // Sell / Purchase payments (show_payments.blade.php)
            'purchase.payments',
            'sell.payments',
            'edit_purchase_payment',
            'delete_purchase_payment',
            'edit_sell_payment',
            'delete_sell_payment',

            // Expense payments
            'all_expense.access',
            'view_own_expense',

            // HMS booking payments
            'hms.add_booking_payment',
            'hms.edit_booking_payment',
            'hms.delete_booking_payment',

            // Gym subscription payments
            'gym.add_subscription_payment',
            'gym.edit_gym_subscription_payment',
            'gym.delete_gym_subscription_payment',

            // Payroll payments (Essentials / HRM)
            'essentials.add_payroll_payment',
            'essentials.edit_payroll_payment',
            'essentials.delete_payroll_payment',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        // Clear permission cache so new permissions apply immediately
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if ($this->command) {
            $this->command->info('Payment permissions seeded (' . count($permissions) . ').');
            $this->command->info('Assign payroll payment permissions to roles from Users → Roles.');
        }
    }
}
