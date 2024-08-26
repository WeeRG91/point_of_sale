<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
       try {
            $data['products'] = DB::table('products')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->select(["products.*", "categories.name as category"])
                ->get();
            return $this->sendResponse("Product list fetched successfully", $data, 200);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function categories(): JsonResponse
    {
       try {
            $data['categories'] = DB::table('categories')
                ->select('id', 'name')
                ->get();
            return $this->sendResponse("Category list fetched successfully", $data, 200);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedProduct = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:products,name',
                'category_id' => 'required',
                'stock' => 'required|numeric',
                'price' => 'required|numeric',
                'image' => 'required|mimes:jpeg,png,jpg|max:5000',
            ]);
            
            if ($validatedProduct->fails()) {
                return $this->sendError("Please enter valid input data", $validatedProduct->errors(), 400);
            }

            $product = $validatedProduct->validated();
            //Store image
            if(!empty($product['image'])){
                $image = $product['image'];
                $imageName = Carbon::now()->timestamp."-".uniqid().".".$image->getClientOriginalExtension();
                
                if(!Storage::disk('public')->exists('products')) {
                    Storage::disk('public')->makeDirectory('products');
                }

                $imagePath = Storage::disk('public')->putFileAs('products', $image, $imageName);
                $product['image'] = $imagePath;
            }

            DB::beginTransaction();
            $data['product'] = Product::create($product);
            DB::commit();

            return $this->sendResponse("Prodcut created successfully", $data, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $data['product'] = Product::with('category:id,name')->find($id);
            
            if (empty($data['product'])) {
                return $this->sendError("Product not found", ["errors" => ['general' => "Product not found"]], 404);
            }

            return $this->sendResponse("Product fetched successfully", $data, 200);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $data['product'] = Product::find($id);
            //dd('update product');
            if (empty($data['product'])) {
                return $this->sendError("Product not found", ["errors" => ['general' => "Product not found"]], 404);
            }

            $validatedProduct = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:products,name,'.$data['product']->id,
                'category_id' => 'required',
                'stock' => 'required|numeric',
                'price' => 'required|numeric',
                'image' => 'sometimes|mimes:jpeg,png,jpg|max:5000',
            ]);

            if ($validatedProduct->fails()) {
                return $this->sendError("Please enter valid input data", $validatedProduct->errors(), 400);
            }

            $product = $validatedProduct->validated();
            //Store image
            if(!empty($product['image'])){
                $image = $product['image'];
                $imageName = Carbon::now()->timestamp."-".uniqid().".".$image->getClientOriginalExtension();
                
                if(!Storage::disk('public')->exists('products')) {
                    Storage::disk('public')->makeDirectory('products');
                }
                if(Storage::disk('public')->exists($data['product']->image)) {
                    Storage::disk('public')->delete($data['product']->image);
                }

                $imagePath = Storage::disk('public')->putFileAs('products', $image, $imageName);
                $product['image'] = $imagePath;
            }
            DB::beginTransaction();
            $data['product'] = $data['product']->update($product);
            DB::commit();

            return $this->sendResponse("Prodcut updated successfully", $data, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
             $data['product'] = Product::find($id);

            if (empty( $data['product'])) {
                return $this->sendError("Product not found", ["errors" => ['general' => "Product not found"]], 404);
            }

            if(Storage::disk('public')->exists($data['product']->image)) {
                Storage::disk('public')->delete($data['product']->image);
            }

            DB::beginTransaction();
            $data['product']->delete();
            DB::commit();

            return $this->sendResponse("Product deleted successfully", $data, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function productList(Request $request) : JsonResponse
    {
        try {
            $query = DB::table('products');
            if(!empty($request->search)) {
                $query->where(function ($query) use ($request) {
                    $query->orWhere('name', 'like', '%'.$request->search.'%');
                });
            }   

            $data['products'] = $query->orderBy('name')->limit(100)->get(['id', DB::raw("name as label"), 'stock', 'price']);
            return $this->sendResponse("Searched products fetched successfully", $data, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
