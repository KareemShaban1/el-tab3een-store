<?php

namespace App\Utils;

use App\Notifications\NewStoreOrderNotification;
use App\User;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;

class StoreOrderNotificationUtil
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function notify(int $business_id, array $payload): void
    {
        $util = new Util;
        $notification = new NewStoreOrderNotification($payload);

        User::where('business_id', $business_id)
            ->user()
            ->get()
            ->filter(function (User $user) use ($business_id, $util) {
                return $util->is_admin($user, $business_id)
                    || $user->can('sell.view')
                    || $user->can('direct_sell.view');
            })
            ->each(function (User $user) use ($notification) {
                $user->notify($notification);
            });
    }

    /**
     * @return array{tab3een: int, servo: int}
     */
    public function getSidebarCounts(User $user, ?int $business_id = null): array
    {
        $business_id = $business_id ?? (int) session('business.id', 0);
        $counts = [
            'tab3een' => 0,
            'servo' => 0,
        ];

        foreach ($user->unreadNotifications as $notification) {
            if (! $this->isStoreOrderNotificationForBusiness($notification, $business_id)) {
                continue;
            }

            $order_type = $notification->data['order_type'] ?? 'tab3een';

            if (in_array($order_type, ['tab3een', 'mixed'], true)) {
                $counts['tab3een']++;
            }

            if (in_array($order_type, ['servo', 'mixed'], true)) {
                $counts['servo']++;
            }
        }

        return $counts;
    }

    public function sidebarBadgeHtml(int $count, string $element_id): string
    {
        if ($count <= 0) {
            return '<span id="'.$element_id.'" class="store-order-unread-badge is-empty"></span>';
        }

        return '<span id="'.$element_id.'" class="store-order-unread-badge">'.$count.'</span>';
    }

    /**
     * @param  array<int, string>  $order_types
     */
    public function markUnreadAsReadForTypes(User $user, array $order_types, ?int $business_id = null): void
    {
        $business_id = $business_id ?? (int) session('business.id', 0);

        foreach ($user->unreadNotifications as $notification) {
            if (! $this->isStoreOrderNotificationForBusiness($notification, $business_id)) {
                continue;
            }

            $order_type = $notification->data['order_type'] ?? 'tab3een';

            if (in_array($order_type, $order_types, true)) {
                $notification->markAsRead();
            }
        }
    }

    public function formatNotificationTime($datetime): string
    {
        if (empty($datetime)) {
            return '';
        }

        $carbon = $datetime instanceof Carbon
            ? $datetime->copy()
            : Carbon::parse($datetime);

        return $carbon->timezone('Africa/Cairo')->format('d/m/Y h:i A');
    }

    private function isStoreOrderNotificationForBusiness(DatabaseNotification $notification, int $business_id): bool
    {
        if ($notification->type !== NewStoreOrderNotification::class) {
            return false;
        }

        if ($business_id <= 0) {
            return true;
        }

        return (int) ($notification->data['business_id'] ?? 0) === $business_id;
    }
}
