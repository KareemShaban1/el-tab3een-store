<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'rating' => 'integer',
        'is_visible' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Public review list, average, and the signed-in customer's own review.
     */
    public static function storefrontPayload(int $businessId, int $productId, ?int $contactId): array
    {
        $forProduct = static::query()
            ->where('business_id', $businessId)
            ->where('product_id', $productId);

        $summaryRow = (clone $forProduct)
            ->where('is_visible', true)
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as review_avg')
            ->first();

        $count = (int) ($summaryRow->review_count ?? 0);
        $average = $count > 0 ? round((float) $summaryRow->review_avg, 1) : 0.0;

        $items = (clone $forProduct)
            ->where('is_visible', true)
            ->with(['contact:id,name'])
            ->latest()
            ->limit(30)
            ->get()
            ->map(function (self $review) {
                return [
                    'id' => $review->id,
                    'name' => $review->contact->name ?? '',
                    'rating' => (int) $review->rating,
                    'comment' => (string) $review->comment,
                    'date' => optional($review->created_at)->translatedFormat('j F Y') ?? '',
                ];
            })
            ->all();

        $mine = null;
        if ($contactId) {
            $own = (clone $forProduct)->where('contact_id', $contactId)->first();
            if ($own) {
                $mine = [
                    'id' => $own->id,
                    'rating' => (int) $own->rating,
                    'comment' => (string) $own->comment,
                    'is_visible' => (bool) $own->is_visible,
                    'date' => optional($own->created_at)->translatedFormat('j F Y') ?? '',
                ];
            }
        }

        return [
            'summary' => [
                'average' => $average,
                'count' => $count,
            ],
            'items' => $items,
            'mine' => $mine,
        ];
    }
};
