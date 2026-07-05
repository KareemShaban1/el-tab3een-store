<?php

namespace App\Http\Controllers;

use App\StorePage;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class StorePageController extends Controller
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
            $pages = StorePage::forBusiness($business_id)
                ->select([
                    'store_pages.id',
                    'store_pages.title',
                    'store_pages.slug',
                    'store_pages.page_type',
                    'store_pages.footer_group',
                    'store_pages.show_in_footer',
                    'store_pages.show_in_header',
                    'store_pages.sort_order',
                    'store_pages.is_active',
                ]);

            return DataTables::of($pages)
                ->editColumn('page_type', function ($row) {
                    return StorePage::pageTypes()[$row->page_type] ?? $row->page_type;
                })
                ->editColumn('footer_group', function ($row) {
                    return StorePage::footerGroups()[$row->footer_group] ?? $row->footer_group;
                })
                ->addColumn('placement', function ($row) {
                    $parts = [];
                    if ((int) $row->show_in_footer === 1) {
                        $parts[] = __('store_pages.footer');
                    }
                    if ((int) $row->show_in_header === 1) {
                        $parts[] = __('store_pages.header');
                    }

                    return $parts !== [] ? implode(', ', $parts) : '-';
                })
                ->editColumn('is_active', function ($row) {
                    return (int) $row->is_active === 1
                        ? '<span class="label bg-green">'.__('business.is_active').'</span>'
                        : '<span class="label bg-red">'.__('lang_v1.inactive').'</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([self::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>
                        <button data-href="'.action([self::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_store_page_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->removeColumn('show_in_footer')
                ->removeColumn('show_in_header')
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return view('store_pages.index');
    }

    public function create()
    {
        $this->authorizeAccess();
        $footer_groups = StorePage::footerGroups();
        $page_types = StorePage::pageTypes();

        return view('store_pages.create', compact('footer_groups', 'page_types'));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        $request->validate($this->validationRules($business_id));

        try {
            $slug = StorePage::normalizeSlug($request->input('slug'), $request->input('title'));

            if (StorePage::forBusiness($business_id)->where('slug', $slug)->exists()) {
                return ['success' => false, 'msg' => __('store_pages.slug_already_exists')];
            }

            StorePage::create([
                'business_id' => $business_id,
                'title' => $request->input('title'),
                'slug' => $slug,
                'content' => $request->input('content'),
                'excerpt' => $request->input('excerpt'),
                'meta_title' => $request->input('meta_title'),
                'meta_description' => $request->input('meta_description'),
                'page_type' => $request->input('page_type', StorePage::PAGE_TYPE_CUSTOM),
                'footer_group' => $request->input('footer_group', StorePage::FOOTER_GROUP_CUSTOMER_SERVICE),
                'sort_order' => (int) $request->input('sort_order', 0),
                'is_active' => $request->boolean('is_active'),
                'show_in_footer' => $request->boolean('show_in_footer'),
                'show_in_header' => $request->boolean('show_in_header'),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
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
        $page = StorePage::forBusiness($business_id)->findOrFail($id);
        $footer_groups = StorePage::footerGroups();
        $page_types = StorePage::pageTypes();

        return view('store_pages.edit', compact('page', 'footer_groups', 'page_types'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $business_id = (int) request()->session()->get('user.business_id');

        $request->validate($this->validationRules($business_id, (int) $id));

        try {
            $page = StorePage::forBusiness($business_id)->findOrFail($id);
            $slug = StorePage::normalizeSlug($request->input('slug'), $request->input('title'));

            if (StorePage::forBusiness($business_id)->where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
                return ['success' => false, 'msg' => __('store_pages.slug_already_exists')];
            }

            $page->update([
                'title' => $request->input('title'),
                'slug' => $slug,
                'content' => $request->input('content'),
                'excerpt' => $request->input('excerpt'),
                'meta_title' => $request->input('meta_title'),
                'meta_description' => $request->input('meta_description'),
                'page_type' => $request->input('page_type', StorePage::PAGE_TYPE_CUSTOM),
                'footer_group' => $request->input('footer_group', StorePage::FOOTER_GROUP_CUSTOMER_SERVICE),
                'sort_order' => (int) $request->input('sort_order', 0),
                'is_active' => $request->boolean('is_active'),
                'show_in_footer' => $request->boolean('show_in_footer'),
                'show_in_header' => $request->boolean('show_in_header'),
                'updated_by' => auth()->id(),
            ]);

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
            StorePage::forBusiness($business_id)->where('id', $id)->delete();
            $output = ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRules(int $business_id, ?int $ignore_id = null): array
    {
        $pageTypes = array_keys(StorePage::pageTypes());
        $footerGroups = array_keys(StorePage::footerGroups());

        return [
            'title' => 'required|string|max:191',
            'slug' => [
                'nullable',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('store_pages', 'slug')
                    ->where(fn ($query) => $query->where('business_id', $business_id))
                    ->ignore($ignore_id),
            ],
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:1000',
            'meta_title' => 'nullable|string|max:191',
            'meta_description' => 'nullable|string|max:500',
            'page_type' => ['nullable', Rule::in($pageTypes)],
            'footer_group' => ['nullable', Rule::in($footerGroups)],
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ];
    }
}
