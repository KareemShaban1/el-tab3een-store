<?php

namespace Modules\Manufacturing\Support;

use Modules\Manufacturing\Utils\ManufacturingUtil;

class PackagingFeature
{
    public static function isGloballyEnabled(): bool
    {
        return (bool) config('manufacturing.packaging_feature.enabled', false);
    }

    public static function isEnabledForBusiness($business_id): bool
    {
        if (! self::isGloballyEnabled()) {
            return false;
        }

        $mfgUtil = new ManufacturingUtil();
        $settings = $mfgUtil->getSettings($business_id);

        // Default ON when global flag is enabled.
        // Only hide when business explicitly disabled the setting.
        if (array_key_exists('enable_packaging_workflow', $settings)) {
            return ! empty($settings['enable_packaging_workflow']);
        }

        return true;
    }

    /**
     * User can open packaging screens (profiles / production).
     */
    public static function userCanAccessPackaging(): bool
    {
        $user = auth()->user();
        if (empty($user)) {
            return false;
        }

        return $user->can('superadmin')
            || $user->can('manufacturing.access_packaging')
            || $user->can('manufacturing.manage_packaging_profiles')
            || $user->can('manufacturing.access_production');
    }

    /**
     * User can create/edit packaging profiles.
     */
    public static function userCanManageProfiles(): bool
    {
        $user = auth()->user();
        if (empty($user)) {
            return false;
        }

        return $user->can('superadmin')
            || $user->can('manufacturing.manage_packaging_profiles')
            || $user->can('manufacturing.access_production');
    }

    public static function shouldHidePackagingMaterialsInPos($business_id): bool
    {
        if (! self::isEnabledForBusiness($business_id)) {
            return false;
        }

        return (bool) config('manufacturing.packaging_feature.hide_packaging_materials_in_pos', true);
    }

    public static function partialCartonPolicy(): string
    {
        return config('manufacturing.packaging_feature.partial_carton_policy', 'strict');
    }

    public static function productUsageTypes(): array
    {
        return config('manufacturing.packaging_feature.product_usage_types', []);
    }
}
