<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Attribute;
use App\Models\Finance\Unit;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\Product;
use App\Models\Finance\UnitConversion;
use App\Http\Requests\Finance\UnitConversionRequest;
use Illuminate\Support\Facades\DB;
use Validator;

class UnitConversionController extends Controller
{
    protected $viewPath = 'finance.products.unitconversions';
    private $route = 'finance.products.unitconversions';
    private $objectModel = UnitConversion::class;

    public function __construct()
    {
        
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $data = $this->objectModel::query();
            $data = $data->with(['product', 'variant']);
            $data = $data->orderBy('id', 'DESC');

            // فلترة حسب المنتج
            if ($request->has('product_id') && !empty($request->product_id)) {
                $data->where('product_id', $request->product_id);
            }

            // فلترة حسب النوع
            if ($request->has('variant_id') && !empty($request->variant_id)) {
                $data->where('variant_id', $request->variant_id);
            }

            // فلترة حسب الوحدة الأساسية
            if ($request->has('base_unit') && !empty($request->base_unit)) {
                $data->where('base_unit', $request->base_unit);
            }

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    $checkbox = '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                                </div>';
                    return $checkbox;
                })
                ->addColumn('product', function ($row) {
                    $product = '';

                    if ($row->product) {
                        $product .= '<a href="'. route('finance.products.show', $row->product_id) .'">
                                    '.$row->product->name.'
                                </a>';
                                if ($row->variant) {
                                    $product .= '<br>
                                    <small class="text-muted">
                                        '.$row->variant->formatted_attributes_text.'
                                    </small>';
                                }
                        
                    } else {
                        $product .= '<span class="text-muted">عام</span>';
                    }

                    return $product;
                })
                ->addColumn('base_unit', function ($row) {
                    $base_unit = Unit::findOrFail($row->base_unit)->name;
                    return $base_unit;
                })
                ->addColumn('target_unit', function ($row) {
                    $target_unit = Unit::findOrFail($row->target_unit)->name;
                    return $target_unit;
                })
                ->addColumn('rate', function ($row) {
                    $rate = '<code>1 '.$row->base_unit.' = '.$row->conversion_rate.' '.$row->target_unit.'</code>';
                    return $rate;
                })
                ->addColumn('rate_reverse', function ($row) {
                    $rate_reverse = '<code>1 '.$row->target_unit.' = '.number_format(1 / $row->conversion_rate, 4).' '.$row->base_unit.'</code>';
                    return $rate_reverse;
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
                    $btn = '<a href="javascript:;" onclick="edit_item(' . $row->id . ')" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['is_default','rate_reverse','rate','target_unit','base_unit','product', 'action', 'checkbox'])
                ->make(true);

        }

        $filters = [
            'units' => Unit::get()
        ];

        return view($this->viewPath . '.index', compact('filters'));
    }

    // For export with filters
    public function export(Request $request)
    {

        $data = $this->objectModel::query();

        // فلترة حسب المنتج
        if ($request->has('product_id')) {
            $data->where('product_id', $request->product_id);
        }

        // فلترة حسب النوع
        if ($request->has('variant_id')) {
            $data->where('variant_id', $request->variant_id);
        }

        // فلترة حسب الوحدة الأساسية
        if ($request->has('base_unit')) {
            $data->where('base_unit', $request->base_unit);
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
            'units' => Unit::get()
        ];

        return view($this->viewPath . '.create', compact('filters'));
    }

    public function store(UnitConversionRequest $request)
    {
        $data = $request->validated();

         // التحقق من عدم تكرار التحويل
        $exists = $this->objectModel::where('product_id', $request->product_id)
            ->where('variant_id', $request->variant_id)
            ->where('base_unit', $request->base_unit)
            ->where('target_unit', $request->target_unit)
            ->exists();

        if ($exists) {
            return back()->with('error', 'تحويل الوحدة هذا موجود بالفعل')->withInput();
        }

        DB::beginTransaction();

        if ($request->is_default) {
            $this->objectModel::where('product_id', $request->product_id)
                ->where('variant_id', $request->variant_id)
                ->where('base_unit', $request->base_unit)
                ->update(['is_default' => false]);
        }

        $result = $this->objectModel::create($data);

        DB::commit();

        return redirect(route($this->route . '.index'))->with('message', 'تم الاضافة بنجاح')->with('status', 'success');
    }

    public function edit($id)
    {
        $data = $this->objectModel::find($id);

        $filters = [
            'units' => Unit::get()
        ];
        
        return view($this->viewPath . '.form', compact('data','filters'));
    }

    public function update(UnitConversionRequest $request)
    {

        $data = $request->validated();

        DB::beginTransaction();

        $conversion = $this->objectModel::whereId($request->id)->first();
        
        // إذا تم تغيير is_default
        if ($request->has('is_default') && $request->is_default) {
            $this->objectModel::where('product_id', $conversion->product_id)
                ->where('variant_id', $conversion->variant_id)
                ->where('base_unit', $conversion->base_unit)
                ->where('id', '!=', $request->id)
                ->update(['is_default' => false]);
        }

        $conversion->update($request->only([
            'conversion_rate',
            'is_default',
            'allow_fractions',
            'decimal_places'
        ]));

        DB::commit();

        return redirect(route($this->route . '.index'))->with('message', 'تم التعديل بنجاح')->with('status', 'success');
    }

    public function destroy(Request $request)
    {

        DB::beginTransaction();
        try {

            $conversions = $this->objectModel::whereIn('id', $request->id)->get();

            foreach ($conversions as $key => $conversion) {

                $conversion->delete();
            }

            DB::commit();

        } catch (\Exception $e) {
            return response()->json(['message' => 'error']);
        }
        return response()->json(['message' => 'success']);
    }

    /**
     * تحويل كمية من وحدة إلى أخرى
     */
    public function convert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|numeric|min:0',
            'from_unit' => 'required|string',
            'to_unit' => 'required|string',
            'allow_fractions' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // البحث عن التحويل
            $conversion = $this->objectModel::where('product_id', $request->product_id)
                ->where('variant_id', $request->variant_id)
                ->where('base_unit', $request->from_unit)
                ->where('target_unit', $request->to_unit)
                ->first();

            if (!$conversion) {
                return response()->json([
                    'success' => false,
                    'message' => 'تحويل الوحدة غير موجود'
                ], 404);
            }

            // التحويل
            $convertedQuantity = $request->quantity * $conversion->conversion_rate;

            // التحقق من الكسور إذا كان ممنوعاً
            if (!$conversion->allow_fractions && !$request->allow_fractions) {
                if (fmod($convertedQuantity, 1) != 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'التحويل ينتج كسوراً غير مسموح بها',
                        'converted_quantity' => $convertedQuantity,
                        'rounded' => round($convertedQuantity)
                    ], 400);
                }
            }

            // التقريب حسب decimal_places
            $roundedQuantity = round($convertedQuantity, $conversion->decimal_places);

            return response()->json([
                'success' => true,
                'conversion' => [
                    'from' => [
                        'quantity' => $request->quantity,
                        'unit' => $request->from_unit
                    ],
                    'to' => [
                        'quantity' => $roundedQuantity,
                        'unit' => $request->to_unit
                    ],
                    'rate' => $conversion->conversion_rate,
                    'is_exact' => $convertedQuantity == $roundedQuantity
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحويل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحويل كمية إلى الوحدة الأساسية
     */
    public function convertToBaseUnit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = Product::findOrFail($request->product_id);
            $baseUnit = $product->unit;

            // إذا كانت الوحدة المدخلة هي الأساسية
            if ($request->unit == $baseUnit) {
                return response()->json([
                    'success' => true,
                    'quantity' => $request->quantity,
                    'unit' => $baseUnit,
                    'is_base_unit' => true
                ]);
            }

            // البحث عن التحويل
            $conversion = $this->objectModel::where('product_id', $request->product_id)
                ->where('base_unit', $request->unit)
                ->where('target_unit', $baseUnit)
                ->first();

            if (!$conversion) {
                return response()->json([
                    'success' => false,
                    'message' => 'تحويل الوحدة غير موجود'
                ], 404);
            }

            $convertedQuantity = $request->quantity * $conversion->conversion_rate;

            return response()->json([
                'success' => true,
                'original' => [
                    'quantity' => $request->quantity,
                    'unit' => $request->unit
                ],
                'converted' => [
                    'quantity' => $convertedQuantity,
                    'unit' => $baseUnit
                ],
                'conversion_rate' => $conversion->conversion_rate
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحويل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على جميع التحويلات الممكنة لوحدة معينة
     */
    public function getConversionsForUnit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'unit' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $conversions = $this->objectModel::where('product_id', $request->product_id)
                ->where('variant_id', $request->variant_id)
                ->where(function ($query) use ($request) {
                    $query->where('base_unit', $request->unit)
                        ->orWhere('target_unit', $request->unit);
                })
                ->get()
                ->map(function ($conversion) use ($request) {
                    return [
                        'id' => $conversion->id,
                        'base_unit' => $conversion->base_unit,
                        'target_unit' => $conversion->target_unit,
                        'conversion_rate' => $conversion->conversion_rate,
                        'is_default' => $conversion->is_default,
                        'direction' => $conversion->base_unit == $request->unit ? 'forward' : 'reverse',
                        'rate' => $conversion->base_unit == $request->unit
                            ? $conversion->conversion_rate
                            : 1 / $conversion->conversion_rate
                    ];
                });

            return response()->json([
                'success' => true,
                'unit' => $request->unit,
                'conversions' => $conversions,
                'count' => $conversions->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب التحويلات: ' . $e->getMessage()
            ], 500);
        }
    }

}
