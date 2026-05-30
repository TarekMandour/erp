<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Unit;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Attribute;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Http\Requests\Finance\ProductVariantRequest;
use Illuminate\Support\Facades\DB;
use Validator;

class ProductVariantController extends Controller
{
    protected $viewPath = 'finance.products.variants';
    private $route = 'finance.products.variants';
    private $objectModel = ProductVariant::class;

    public function __construct()
    {
        
    }

    public function index(Request $request, $productId)
    {

        if ($request->ajax()) {
            $query = $this->objectModel::query()->where('product_id', $productId);

            // فلترة حسب الاسم أو SKU
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('sku', 'LIKE', "%{$search}%")
                        ->orWhere('barcode', 'LIKE', "%{$search}%");
                });
            }

            return Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    $checkbox = '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                                </div>';
                    return $checkbox;
                })
                ->addColumn('sku', function ($row) {
                    $sku = $row->sku;
                    $sku .= '<br><small class="text-muted">'.$row->barcode.'</small>';
                    return $sku;
                })
                ->addColumn('attributes', function ($row) {
                    $attributes = '';

                    if ($row->attributes && count($row->attributes)) {
                        foreach ($row->attributes as $key => $value) {
                            $attribute = Attribute::find($key);
                            $label = $attribute ? $attribute->name : $key;
                            $attributes .= '<span class="badge bg-light-info me-1 mb-1">' . e($label) . ': ' . e($value) . '</span>';
                        }
                    } else {
                        $attributes = '<span class="text-muted">---</span>';
                    }

                    return $attributes;
                })
                ->addColumn('price', function ($row) {
                    $price = '<strong class="text-success">'.number_format($row->selling_price, 2).'</strong>';
                    $price .= '<br><small class="text-muted">التكلفة: '.number_format($row->purchase_price, 2).'</small>';
                    return $price;
                })
                ->addColumn('stock', function ($row) {
                    // $stocks = $row->inventoryLots->sum('quantity');
                    $stocks = 0;
                    $stock = '<span
                                class="label label-lg font-weight-bold label-light-'. $stocks > $row->alert_quantity ? 'success' : 'danger' .' label-inline">
                                '. $stocks .'
                            </span>';

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
                ->addColumn('is_default', function ($row) {
                    if($row->is_default) {
                        $is_default = '<span class="badge bg-light-primary">افتراضي</span>';
                    } else {
                        $is_default = '';
                    }
                    return $is_default;
                })
                ->addColumn('action', function ($row) {
                    $btn = '<a href="'.route($this->route.'.edit', [$row->product_id, $row->id]).'" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['is_default','is_active','stock','price','attributes','sku','action', 'checkbox'])
                ->make(true);

        }

        return view($this->viewPath . '.index', compact('productId'));
    }

    public function create($productId)
    {
        $filters = [
            'attributes' => Attribute::get(),
        ];

        return view($this->viewPath . '.create', compact('filters','productId'));
    }

    public function store(ProductVariantRequest $request, $productId)
    {

        $data = $request->validated();

        $data['product_id'] = $productId ;

        $attribute_array = collect($request->kt_docs_repeater_basic)
            ->filter(fn ($item) =>
                !is_null($item['attri_keys']) &&
                !is_null($item['attri_values'])
            )
            ->values() // reset index
            ->toArray();

        if ($request->has('kt_docs_repeater_basic')) {
            $keys = array_column($attribute_array, 'attri_keys');
            $values = array_column($attribute_array, 'attri_values');

            $attributes = array_combine($keys, $values);
            $data['attributes'] = $attributes;
        }

        DB::beginTransaction();

        if ($request->is_default) {
            ProductVariant::where('product_id', $productId)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $result = $this->objectModel::create($data);

        DB::commit();

        return redirect(route($this->route . '.index', $productId))->with('message', 'تم الاضافة بنجاح')->with('status', 'success');
    }

    public function edit($productId, $id)
    {
        $data = $this->objectModel::find($id);

        $filters = [
            'attributes' => Attribute::get(),
        ];

        return view($this->viewPath . '.edit', compact('data','filters','productId'));
    }

    public function update(ProductVariantRequest $request, $productId)
    {

        $data = $request->validated();

        $attribute_array = collect($request->kt_docs_repeater_basic)
            ->filter(fn ($item) =>
                !is_null($item['attri_keys']) &&
                !is_null($item['attri_values'])
            )
            ->values() // reset index
            ->toArray();

        $data['product_id'] = $productId ;
        $variantId = $request->id;

        if ($request->has('kt_docs_repeater_basic')) {
            $keys = array_column($attribute_array, 'attri_keys');
            $values = array_column($attribute_array, 'attri_values');

            $attributes = array_combine($keys, $values);
            $data['attributes'] = $attributes;
        }

        DB::beginTransaction();

        if ($request->has('is_default') && $request->is_default) {
            ProductVariant::where('product_id', $productId)
                ->where('id', '!=', $variantId)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $result = $this->objectModel::whereId($variantId)->first();

        $result->update($data);

        DB::commit();

        return redirect(route($this->route . '.index', $productId))->with('message', 'تم التعديل بنجاح')->with('status', 'success');
    }

    public function destroy(Request $request, $productId)
    {
        DB::beginTransaction();

        try {
            $variants = ProductVariant::where('product_id', $productId)
                ->whereIn('id', $request->id)->get();

            foreach ($variants as $key => $variant) {

                // $totalStock = $variant->inventoryLots()->sum('quantity');
                // if ($totalStock > 0) {
                //     return back()->with('error', 'لا يمكن حذف النوع لأنه يحتوي على مخزون');
                // }

                // إذا كان النوع افتراضي، تحديث المنتج
                if ($variant->is_default) {
                    $newDefault = ProductVariant::where('product_id', $productId)
                        ->where('id', '!=', $variant->id)
                        ->where('is_active', true)
                        ->first();
                }

                $variant->delete();

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
    public function generateSku($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            $productSku = $product->sku;

            // عد الأنواع الحالية
            $variantCount = ProductVariant::where('product_id', $productId)->count();
            $nextNumber = $variantCount + 1;

            // توليد SKU: SKU-المنتج-الرقم
            $generatedSku = $productSku . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // التأكد من أنه فريد
            while (ProductVariant::where('sku', $generatedSku)->exists()) {
                $nextNumber++;
                $generatedSku = $productSku . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            return response()->json([
                'success' => true,
                'sku' => $generatedSku,
                'suggestions' => [
                    $productSku . '-001',
                    $productSku . '-' . strtoupper(substr(md5(uniqid()), 0, 4)),
                    $productSku . '-' . date('Ymd') . '-' . $nextNumber
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في توليد SKU: ' . $e->getMessage()
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {

            $productId = $request->product_id;

            if (!$productId) {
                return response()->json(['results' => []]);
            }

            $variants = ProductVariant::where('product_id', $productId)->get();

            return response()->json([
                'results' => $variants->map(fn ($v) => [
                    'id'   => $v->id,
                    'text' => $v->formatted_attributes_text
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في عرض الانواع: ' . $e->getMessage()
            ], 500);
        }
    }
}
