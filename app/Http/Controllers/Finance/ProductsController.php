<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Unit;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\Category;
use App\Models\Finance\Brand;
use App\Models\Finance\Product;
use App\Http\Requests\Finance\ProductRequest;
use Illuminate\Support\Facades\DB;
use Validator;

class ProductsController extends Controller
{
    protected $viewPath = 'finance.products';
    private $route = 'finance.products';
    private $objectModel = Product::class;

    public function __construct()
    {
        
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $query = $this->objectModel::query();

            // فلترة حسب الاسم أو SKU
            if ($request->has('search') && $request->search != '' || $request->has('fsearch') && $request->fsearch != '') {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('sku', 'LIKE', "%{$search}%")
                        ->orWhere('barcode', 'LIKE', "%{$search}%")
                        ->orWhereHas('variants', function ($q) use ($search) {
                            $q->where('sku', 'LIKE', "%{$search}%")
                                ->orWhere('barcode', 'LIKE', "%{$search}%");
                        });
                });
            }

            // فلترة حسب الفئة
            if ($request->has('category_id') && $request->category_id != '') {
                $query->where('category_id', $request->category_id);
            }

            // فلترة حسب العلامة التجارية
            if ($request->has('brand_id') && $request->brand_id != '') {
                $query->where('brand_id', $request->brand_id);
            }

            // فلترة حسب حالة المنتج
            if ($request->has('is_active') && $request->is_active != '') {
                $query->where('is_active', $request->is_active);
            }

            // الترتيب
            $orderBy = $request->order_by ?? 'created_at';
            $orderDirection = $request->order_direction ?? 'desc';
            $query->orderBy($orderBy, $orderDirection);

            return Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    $checkbox = '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                                </div>';
                    return $checkbox;
                })
                ->addColumn('thumbnail', function ($row) {
                    if ($row->getMedia('thumbnail')->count() > 0) {
                        $thumbnail = '<img src="'.$row->getFirstMediaUrl('thumbnail').'" alt="'.$row->name.'"
                                    style="width: 50px; height: 50px; object-fit: cover;" class="rounded">';
                    } else {
                        $thumbnail = '<img src="'.asset('dash/assets/media/svg/files/blank-image.svg').'" alt="'.$row->name.'"
                                    style="width: 50px; height: 50px; object-fit: cover;" class="rounded">';   
                    }

                    return $thumbnail;
                })
                ->addColumn('name', function ($row) {
                    $name = '<strong>'.$row->name.'</strong>';
                    $name .= '<br><small class="text-muted">'.($row->brand?->name ?? '--').'</small>';
                    return $name;
                })
                ->addColumn('sku', function ($row) {
                    $sku = $row->sku;
                    $sku .= '<br><small class="text-muted">'.$row->barcode.'</small>';
                    return $sku;
                })
                ->addColumn('category', function ($row) {
                    $category = ($row->category?->name ?? '--');
                    return $category;
                })
                ->addColumn('price', function ($row) {
                    $price = '<strong class="text-success">'.number_format($row->selling_price, 2).'</strong>';
                    $price .= '<br><small class="text-muted">التكلفة: '.number_format($row->purchase_price, 2).'</small>';
                    return $price;
                })
                ->addColumn('stock', function ($row) {
                    $totalStock = 0;
                    // foreach ($row->variants as $variant) {
                    //     $totalStock += $variant->inventoryLots->sum('quantity');
                    // }

                    if ($totalStock > 0) {
                        $stock = '<span class="badge bg-light-success">'.$totalStock.' '.($row->unit?->name ?? '--').'</span>';
                    } else {
                        $stock = '<span class="badge bg-light-danger">نفذ</span>';
                    }

                    if($row->has_variants) {
                        $stock .= '<br><small class="text-muted">'.$row->variants->count().' نوع</small>';
                    }

                    return $stock;
                })
                ->addColumn('is_active', function ($row) {
                    if($row->is_active) {
                        $is_active = '<span class="badge bg-light-success">نشط</span>';
                    } else {
                        $is_active = '<span class="badge bg-light-danger">غير نشط</span>';
                    }
                    return $is_active;
                })
                ->addColumn('date', function ($row) {
                    $date = $row->created_at->format('Y-m-d');
                    return $date;
                })
                ->addColumn('action', function ($row) {
                    $btn = '<a href="' . route($this->route.'.variants.index', $row->id) . '" class="btn btn-xs btn-icon btn-info me-2"><i class="bi bi-eye fs-4"></i></a>';
                    $btn .= '<a href="'.route($this->route.'.edit', $row->id).'" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['date','is_active','stock','price','category','name','sku','thumbnail','action', 'checkbox'])
                ->make(true);

        }

        $filters = [
            'categories' => Category::where('is_active', true)->get(),
            'brands' => Brand::where('is_active', true)->get(),
            'units' => Unit::get()
        ];

        $stats = $this->getProductsStats($this->objectModel::query());

        return view($this->viewPath . '.index', compact('filters', 'stats'));
    }

    private function getProductsStats($query)
    {
        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'with_variants' => Product::where('has_variants', true)->count(),
            'with_expiry' => Product::where('has_expiry', true)->count(),
            // 'in_stock' => Product::whereHas('lots', function ($q) {
            //     $q->where('quantity', '>', 0)->where('status', 'active');
            // })->count(),
            // 'out_of_stock' => Product::whereDoesntHave('lots', function ($q) {
            //     $q->where('quantity', '>', 0)->where('status', 'active');
            // })->count(),
            'categories_distribution' => Product::select('category_id', DB::raw('COUNT(*) as count'))
                ->groupBy('category_id')
                ->with('category')
                ->get()
        ];

        return $stats;
    }

    // For export with filters
    public function export(Request $request)
    {

        $data = $this->objectModel::query();

        if (!empty($request->search) || !empty($request->fsearch)) {
            $search = $request->search ?? $request->fsearch;
            $data->search($search);
        }

        if (!empty($request->maincategory)) {
            $data->mainCategories();
        }

        if (!empty($request->is_active)) {
            if ($request->is_active == 'true') {
                $data->active(); 
            } else {
                $data->notActive();
            }
        }

        $data = $data->get();

        return (new FastExcel($data))->download('file.csv');

    }

    public function show($id)
    {
        $data = $this->objectModel::find($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function create()
    {
        $filters = [
            'categories' => Category::where('is_active', true)->get(),
            'brands' => Brand::where('is_active', true)->get(),
            'units' => Unit::get()
        ];

        return view($this->viewPath . '.create', compact('filters'));
    }

    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        $result = $this->objectModel::create($data);

        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            $result->clearMediaCollection('thumbnail');
            $result->addMediaFromRequest('thumbnail')->toMediaCollection('thumbnail');
        }

        if ($request->hasFile('gallery')) {
            $result->clearMediaCollection('gallery');
            foreach ($request->file('gallery') as $image) {
                $result->addMedia($image)->toMediaCollection('gallery');
            }
        }

        return redirect(route($this->route . '.index'))->with('message', 'تم الاضافة بنجاح')->with('status', 'success');
    }

    public function edit($id)
    {
        $data = $this->objectModel::find($id);

        $filters = [
            'categories' => Category::where('is_active', true)->get(),
            'brands' => Brand::where('is_active', true)->get(),
            'units' => Unit::get()
        ];

        return view($this->viewPath . '.edit', compact('data','filters'));
    }

    public function update(ProductRequest $request)
    {

        $data = $request->validated();

        $result = $this->objectModel::whereId($request->id)->first();

        $result->update($data);

        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            $result->clearMediaCollection('thumbnail');
            $result->addMediaFromRequest('thumbnail')->toMediaCollection('thumbnail');
        }

        if ($request->hasFile('gallery')) {
            $result->clearMediaCollection('gallery');
            foreach ($request->file('gallery') as $image) {
                $result->addMedia($image)->toMediaCollection('gallery');
            }
        }

        return redirect(route($this->route . '.index'))->with('message', 'تم التعديل بنجاح')->with('status', 'success');
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $products = $this->objectModel::with(['variants'])->whereIn('id', $request->id)->get();
            // $products = $this->objectModel::with(['variants', 'orderItems'])->whereIn('id', $request->id)->get();

            foreach ($products as $key => $product) {
                // التحقق من وجود مبيعات
                // if ($product->orderItems()->count() > 0) {
                //     return back()->with('message', 'لا يمكن حذف المنتج ('.$product->name.') لأنه مرتبط بمبيعات')->with('status', 'error');
                // }

                // التحقق من وجود مخزون
                // $hasStock = false;
                // foreach ($product->variants as $variant) {
                //     if ($variant->inventoryLots()->sum('quantity') > 0) {
                //         $hasStock = true;
                //         break;
                //     }
                // }

                // if ($hasStock) {
                //     return back()->with('message', 'لا يمكن حذف المنتج لأنه يحتوي على مخزون')->with('status', 'error');
                // }

                // حذف الأنواع أولاً
                $product->variants()->delete();

                // حذف الصور
                $product->clearMediaCollection('product_images');

                // حذف المنتج
                $product->delete();

                DB::commit();
            }

        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => "عفوا لم يتم الحذف"]);
        }
        return response()->json(['status' => 'success','message' => 'تم الحذف بنجاح']);
    }

    /**
     * توليد SKU تلقائي
     */
    public function generateSku(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'nullable|exists:units,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $prefix = '';

            if (1) {
                $category = Category::find(1);
                if ($category && $category->code) {
                    $prefix .= substr($category->code, 0, 3).'-';
                }
            }

            if (1) {
                $brand = Brand::find(1);
                if ($brand && $brand->code) {
                    $prefix .= substr($brand->code, 0, 3);
                }
            }

            if (empty($prefix)) {
                $prefix = 'PROD';
            }

            // البحث عن آخر SKU بنفس البادئة
            $lastProduct = Product::where('sku', 'LIKE', $prefix . '%')
                ->orderBy('sku', 'desc')
                ->first();
            if ($lastProduct) {
                $lastNumber = intval(substr($lastProduct->sku, strlen($prefix)+1));
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $generatedSku = $prefix .'-'. str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // التأكد من أنه فريد
            while (Product::where('sku', $generatedSku)->exists()) {
                $nextNumber++;
                $generatedSku = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }

            return response()->json([
                'success' => true,
                'sku' => $generatedSku,
                'prefix' => $prefix,
                'next_number' => $nextNumber,
                'suggestions' => [
                    $generatedSku,
                    $prefix . '-' . strtoupper(substr(md5(uniqid()), 0, 6)),
                    $prefix . date('ymd') . str_pad($nextNumber, 3, '0', STR_PAD_LEFT)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في توليد SKU: ' . $e->getMessage()
            ], 500);
        }
    }

    public function ajaxSearch(Request $request)
    {
        $search = $request->search;

        $products = Product::query()
            ->select('id', 'name', 'sku')
            ->where('name', 'like', "%{$search}%")
            ->orWhere('sku', 'like', "%{$search}%")
            ->paginate(20);

        return response()->json([
            'results' => $products->map(fn ($p) => [
                'id' => $p->id,
                'text' => "{$p->name} ({$p->sku})",
            ]),
            'pagination' => [
                'more' => $products->hasMorePages()
            ]
        ]);
    }
}
