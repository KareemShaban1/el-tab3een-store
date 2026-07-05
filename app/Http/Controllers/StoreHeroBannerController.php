<?php

namespace App\Http\Controllers;

use App\StoreHeroBanner;
use App\Utils\Util;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StoreHeroBannerController extends Controller
{
    public function __construct(private Util $util) {}

    private function authorizeAccess(): void
    {
        $business_id = (int) request()->session()->get('user.business_id');
        $is_admin = $this->util->is_admin(auth()->user(), $business_id);

        if (! $is_admin && ! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $banners = StoreHeroBanner::forBusiness($business_id)
                ->select([
                    'store_hero_banners.id',
                    'store_hero_banners.badge',
                    'store_hero_banners.title',
                    'store_hero_banners.link_title',
                    'store_hero_banners.link_url',
                    'store_hero_banners.image',
                    'store_hero_banners.sort_order',
                    'store_hero_banners.is_active',
                ]);

            return DataTables::of($banners)
                ->editColumn('title', function ($row) {
                    return strip_tags((string) $row->title);
                })
                ->editColumn('image', function ($row) {
                    $url = $row->image_url;
                    if (empty($url)) {
                        return '-';
                    }

                    return '<img src="'.e($url).'" alt="" style="max-height:48px;max-width:96px;border-radius:6px;">';
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="label bg-green">'.__('business.is_active').'</span>'
                        : '<span class="label bg-red">'.__('lang_v1.inactive').'</span>';
                })
                ->addColumn('link', function ($row) {
                    if (empty($row->link_title)) {
                        return '-';
                    }

                    $url = $row->link_url ?: '#';

                    return e($row->link_title).' <small class="text-muted">('.e($url).')</small>';
                })
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([self::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>
                        <button data-href="'.action([self::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_hero_banner_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->removeColumn('link_title')
                ->removeColumn('link_url')
                ->rawColumns(['image', 'link', 'is_active', 'action'])
                ->make(true);
        }

        return view('store_hero_banners.index');
    }

    public function create()
    {
        $this->authorizeAccess();

        return view('store_hero_banners.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        $request->validate([
            'title' => 'required|string|max:2000',
            'badge' => 'nullable|string|max:191',
            'content' => 'nullable|string|max:5000',
            'link_title' => 'nullable|string|max:191',
            'link_url' => 'nullable|string|max:500',
            'image_alt' => 'nullable|string|max:191',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'image' => 'nullable|image|max:5120',
        ]);

        try {
            $image_name = $this->util->uploadFile($request, 'image', 'hero_banners', 'image');

            StoreHeroBanner::create([
                'business_id' => $business_id,
                'badge' => $request->input('badge'),
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'link_title' => $request->input('link_title'),
                'link_url' => $request->input('link_url'),
                'image_alt' => $request->input('image_alt'),
                'sort_order' => (int) $request->input('sort_order', 0),
                'is_active' => $request->boolean('is_active'),
                'image' => $image_name,
            ]);

            $output = ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function edit($id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');
        $banner = StoreHeroBanner::forBusiness($business_id)->findOrFail($id);

        return view('store_hero_banners.edit', compact('banner'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        $request->validate([
            'title' => 'required|string|max:2000',
            'badge' => 'nullable|string|max:191',
            'content' => 'nullable|string|max:5000',
            'link_title' => 'nullable|string|max:191',
            'link_url' => 'nullable|string|max:500',
            'image_alt' => 'nullable|string|max:191',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'image' => 'nullable|image|max:5120',
        ]);

        try {
            $banner = StoreHeroBanner::forBusiness($business_id)->findOrFail($id);
            $data = [
                'badge' => $request->input('badge'),
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'link_title' => $request->input('link_title'),
                'link_url' => $request->input('link_url'),
                'image_alt' => $request->input('image_alt'),
                'sort_order' => (int) $request->input('sort_order', 0),
                'is_active' => $request->boolean('is_active'),
            ];

            if ($request->hasFile('image')) {
                $data['image'] = $this->util->uploadFile($request, 'image', 'hero_banners', 'image');
            }

            $banner->update($data);

            $output = ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function destroy($id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        try {
            $banner = StoreHeroBanner::forBusiness($business_id)->findOrFail($id);
            $banner->delete();

            $output = ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
