<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListingResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSelectReource;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use Exception;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image as Image;

class ProductController extends Controller
{
    // define middleware
    public function __construct()
    {
        $this->middleware('can:product-list', ['only' => ['index', 'search']]);
        $this->middleware('can:product-create', ['only' => ['create']]);
        $this->middleware('can:product-view', ['only' => ['show']]);
        $this->middleware('can:product-edit', ['only' => ['update']]);
        $this->middleware('can:product-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return ProductListingResource::collection(Product::with('proSubCategory.category', 'productUnit', 'productTax',
            'productBrand')->latest()->paginate($request->perPage));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validate request
        $this->validate($request, [
            'itemName' => 'required|string|max:255|unique:products,name',
            'itemCode' => 'required|numeric|max:99999999999|unique:products,code',
            'itemModel' => 'nullable|string|min:2|max:255',
            'barcodeSymbology' => 'required|string|max:20',
            'subCategory' => 'required',
            'brand' => 'nullable',
            'itemUnit' => 'required',
            'productTax' => 'required',
            'taxType' => 'required',
            'regularPrice' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'note' => 'nullable|string|max:255',
            'alertQuantity' => 'nullable|numeric|min:1|max:255',
        ]);
        try {
            // generate code
            $code = 1;
            if ($request->itemCode) {
                //$code = ltrim($request->itemCode, '0');
                $code = $request->itemCode;
            } else {
                $product = Product::latest()->first();
                if ($product) {
                    $code = $product->code + 1;
                }
            }

            // upload thumbnail and set the name
            $imageName = '';
            if ($request->image) {
                $imageName = time().'.'.explode('/',
                        explode(':', substr($request->image, 0, strpos($request->image, ';')))[1])[1];
                Image::make($request->image)->save(public_path('images/products/').$imageName);
            }

            $brand = $tax = $discount = null;
            if (isset($request->brand)) {
                $brand = $request->brand['id'];
            }
            if (isset($request->productTax)) {
                $tax = $request->productTax['id'];
            }
            if ($request->discount) {
                $discount = $request->discount;
            }

            // create product
            $product = Product::create([
                'name' => $request->itemName,
                'code' => $code,
                'model' => $request->itemModel,
                'barcode_symbology' => $request->barcodeSymbology,
                'sub_cat_id' => $request->subCategory['id'],
                'brand_id' => $brand,
                'unit_id' => $request->itemUnit['id'],
                'tax_id' => $tax,
                'tax_type' => $request->taxType,
                'regular_price' => $request->regularPrice,
                'discount' => $discount,
                'note' => clean($request->note),
                'alert_qty' => $request->alertQuantity,
                'status' => $request->status,
                'image_path' => $imageName,
            ]);

            return $this->responseWithSuccess('Product added successfully');
        } catch (Exception $e) {
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        try {
            $product = Product::where('slug', $slug)->with('proSubCategory.category')->first();

            return new ProductResource($product);
        } catch (Exception $e) {
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $product = Product::where('slug', $slug)->first();
        // validate request
        $this->validate($request, [
            'itemName' => 'required|string|max:255|unique:products,name,'.$product->id,
            'itemCode' => 'required|numeric|max:99999|unique:products,code,'.$product->id,
            'itemModel' => 'nullable|string|min:2|max:255',
            'barcodeSymbology' => 'required|string|max:20',
            'subCategory' => 'required',
            'brand' => 'nullable',
            'itemUnit' => 'required',
            'productTax' => 'required',
            'taxType' => 'required',
            'regularPrice' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'note' => 'nullable|string|max:255',
            'alertQuantity' => 'nullable|numeric|min:1|max:1000',
        ]);
        try {
            // upload thumbnail and set the name
            $imageName = $product->image_path;
            if ($request->image) {
                if ($imageName) {
                    @unlink(public_path('images/products/'.$imageName));
                }
                $imageName = time().'.'.explode('/',
                        explode(':', substr($request->image, 0, strpos($request->image, ';')))[1])[1];
                Image::make($request->image)->save(public_path('images/products/').$imageName);
            }

            $brand = $product->brand_id;
            if (isset($request->brand)) {
                $brand = $request->brand['id'];
            }
            $tax = $product->tax_id;
            if (isset($request->productTax)) {
                $tax = $request->productTax['id'];
            }
            $discount = $product->discount;
            if ($request->discount) {
                $discount = $request->discount;
            }

            // udpate product
            $product->update([
                'name' => $request->itemName,
                'code' => $request->itemCode,
                'model' => $request->itemModel,
                'barcode_symbology' => $request->barcodeSymbology,
                'sub_cat_id' => $request->subCategory['id'],
                'brand_id' => $brand,
                'unit_id' => $request->itemUnit['id'],
                'tax_id' => $tax,
                'tax_type' => $request->taxType,
                'regular_price' => $request->regularPrice,
                'discount' => $discount,
                'note' => clean($request->note),
                'alert_qty' => $request->alertQuantity,
                'status' => $request->status,
                'image_path' => $imageName,
            ]);

            return $this->responseWithSuccess('Product updated successfully');
        } catch (Exception $e) {
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        try {
            $product = Product::where('slug', $slug)->first();
            //delete image from storage
            if ($product->image_path) {
                @unlink(public_path('images/products/'.$product->image_path));
            }
            $product->delete();

            return $this->responseWithSuccess('Product deleted successfully');
        } catch (Exception $e) {
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * search resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function search(Request $request)
    {
        $term = $request->term;

        $query = Product::with('proSubCategory.category')->where('name', 'LIKE', '%'.$term.'%')
            ->orWhere('slug', 'LIKE', '%'.$term.'%')
            ->orWhere('model', 'LIKE', '%'.$term.'%')
            ->orWhere('code', 'LIKE', '%'.$term.'%')
            ->orWhere('regular_price', 'LIKE', '%'.$term.'%')
            ->orWhere('purchase_price', 'LIKE', '%'.$term.'%')
            ->orWhereHas('proSubCategory', function ($newQuery) use ($term) {
                $newQuery->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhereHas('category', function ($newQuery) use ($term) {
                        $newQuery->where('name', 'LIKE', '%'.$term.'%');
                    });
            });

        return ProductListingResource::collection($query->orderBy('code', 'ASC')->paginate($request->perPage));
    }

    /**
     * search resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function searchFromPos(Request $request)
    {
        $term = $request->term;
        $query = Product::with('proSubCategory.category');
        if (isset($request->catSlug) && isset($request->subCatSlug)) {
            $subCategory = ProductSubCategory::where('slug', $request->subCatSlug)->first();
            $query = $query->where('sub_cat_id', $subCategory->id);
        } elseif (isset($request->catSlug) && !isset($request->subCatSlug)) {
            $category = ProductCategory::where('slug', $request->catSlug)->firstOrFail();
            $subCategories = ProductSubCategory::where('cat_id', $category->id)->pluck('id');
            $query = $query->whereIn('sub_cat_id', $subCategories);
        }
        $query = $query->where(function ($query) use ($term) {
            $query->where('name', 'LIKE', '%' . $term . '%')
                ->orWhere('slug', 'LIKE', '%' . $term . '%')
                ->orWhere('model', 'LIKE', '%' . $term . '%')
                ->orWhere('code', 'LIKE', '%' . $term . '%');
        });
        return ProductSelectReource::collection($query->orderBy('code', 'ASC')->paginate($request->perPage));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allProducts()
    {
        $products = Product::with('purchaseProducts', 'adjustmentProducts', 'invoiceProducts', 'invoiceReturnProducts',
            'productTax')->where('status', 1)->latest()->get();

        return ProductSelectReource::collection($products);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allProductsForSelect()
    {
        $products = Product::with('purchaseProducts', 'adjustmentProducts', 'invoiceProducts', 'invoiceReturnProducts',
            'productTax')->where('status', 1)->latest()->get();

        return ProductSelectReource::collection($products);
    }

    // generate item code
    public function generateItemCode()
    {
        // generate code
        $code = 1;
        $product = Product::latest()->first();
        if ($product) {
            $code = $product->code + 1;
        }
        $prefix = '';
        $setting = GeneralSetting::get()->where('key', 'product_prefix')->first();
        $prefix = $setting ? $setting->value : '';

        return [
            'prefix' => $prefix,
            'code' => str_pad($code, 6, '0', STR_PAD_LEFT),
        ];
    }

    // return products by sub category
    public function productsBySubCategory($catSlug, $subCatSlug)
    {
        if ($catSlug == 'all' && $subCatSlug == 'all') {
            $products = Product::latest()->get();
        } elseif ($catSlug != 'all' && $subCatSlug == 'all') {
            $category = ProductCategory::where('slug', $catSlug)->first();
            $products = Product::with('proSubCategory.category')->whereHas('proSubCategory',
                function ($newQuery) use ($category) {
                    $newQuery->whereHas('category', function ($newQuery) use ($category) {
                        $newQuery->where('id', $category->id);
                    });
                })->get();
        } else {
            $subCat = ProductSubCategory::where('slug', $subCatSlug)->first();
            $products = Product::where('sub_cat_id', $subCat->id)->latest()->get();
        }

        return ProductResource::collection($products);
    }

    // return all products by sub category
    public function allProductsBySubCategory($catSlug, $subCatSlug)
    {
        if ($catSlug == 'all' && $subCatSlug == 'all') {
            $products = Product::latest()->get();
        } elseif ($catSlug != 'all' && $subCatSlug == 'all') {
            $category = ProductCategory::where('slug', $catSlug)->first();
            $products = Product::with('proSubCategory.category')->whereHas('proSubCategory',
                function ($newQuery) use ($category) {
                    $newQuery->whereHas('category', function ($newQuery) use ($category) {
                        $newQuery->where('id', $category->id);
                    });
                })->get();
        } else {
            $subCat = ProductSubCategory::where('slug', $subCatSlug)->first();
            $products = Product::where('sub_cat_id', $subCat->id)->latest()->paginate(5);
        }

        return ProductSelectReource::collection($products);
    }
}
