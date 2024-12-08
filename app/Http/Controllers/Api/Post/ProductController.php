<?php

namespace App\Http\Controllers\Api\Post;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ProductService;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    protected $productService;
    protected $activityService;

    
    public function __construct(ProductService $productService, ActivityService $activityService){
        $this->productService = $productService;
        $this->activityService = $activityService;
    }

    public function createProduct(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'title' => 'required|string',
                'image' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'description' => 'required|array',
                'price' => 'required|string',
                'links' => 'required|array',
                'reviews' => 'array',
                'parent_post_id' => 'required|numeric',
                'affiliate_posts' => 'array',
            ]);
            
            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }
            
            $user = auth()->user();
            $email = $user->email;

            $imagePath = null;

            if ($request->hasFile('image')) { 
                $imagePath = $request->file('image')->store("images/product", 'public');
            }

            $data = [
                'name' => $request->input('name'),
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'price' => $request->input('price'),
                'links' => $request->input('links'),
                'reviews' => $request->input('reviews', []), 
                'parent_post_id' => $request->input('parent_post_id'),
                'affiliate_posts' => $request->input('affiliate_posts', []),
                'created_by' => $email,
                'image' => $imagePath, 
            ];

            $existingProduct = $this->productService->checkForExistingProduct($data);

            if($existingProduct) {
                return $this->sendResponse(false, 'A product with the same content and post id already exists.', [], 400);
            }

            $product = $this->productService->createProduct($data);
           
            $title = 'Product Created';
            $action = "A new product with the id of $product->id was created";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Product Created Successfully', ["data" => $product], 201);
        }catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) { 
                return $this->sendResponse(false, 'The specified parent_post_id does not exist.', [], 400);
            }
    
            return $this->sendResponse(false, 'A database error occurred', ['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while creating product for a post', ['error' => $e->getMessage()], 500);
        }
    }

    public function getProduct(Request $request)
    {
        try {
            if ($request->has('id')) {
                $id = $request->query('id');

                $product = $this->productService->findProductById($id); 
                if (!$product) {
                    return $this->sendResponse(false, 'Product not found', [], 400);
                }
                return $this->sendResponse(true, 'Product fetched successfully', ['data' => $product], 200);
            }
    
           
            if ($request->has('name')) {
                $name = $request->query('name');
                $products = $this->productService->findProductById($name)->get();
                return $this->sendResponse(true, 'Products fetched successfully', ['data' => $products], 200);
            }

            if ($request->has('title')) {
                $name = $request->query('title');
                $products = $this->productService->findProductByTitle($title)->get();
                return $this->sendResponse(true, 'Products fetched successfully', ['data' => $products], 200);
            }
    
            
            $products = $this->productService->allProduct();
            return $this->sendResponse(true, 'All products fetched successfully', ['data' => $products], 200);
    
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while getting the products', ['error' => $e->getMessage()], 500);
        }
    }

    public function editProduct(Request $request)
    {
        try {
            $id = $request->route('id');
    
            if (empty($id) || !is_numeric($id)) {
                return $this->sendResponse(false, 'ID cannot be empty and must be a numeric value.', [], 400);
            }
    
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string',
                'title' => 'sometimes|string',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'description' => 'sometimes|array',
                'price' => 'sometimes|string',
                'links' => 'sometimes|array',
                'reviews' => 'sometimes|array',
                'parent_post_id' => 'sometimes|numeric',
            ]);
    
            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }
    
            $product = $this->productService->findProductById($id);
    
            if (empty($product)) {
                return $this->sendResponse(false, 'Product not found.', [], 404);
            }
    
            $user = auth()->user();
            $email = $user->email;
    
            $imagePath = $product->image; 
            if ($request->hasFile('image')) {
                if (!empty($imagePath) && Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = $request->file('image')->store('images/product', 'public');
            }


            $product->name = $request->input('name', $product->name);
            $product->title = $request->input('title', $product->title);
            $product->description = $request->has('description') ? json_encode($request->input('description')) : $product->description;
            $product->price = $request->input('price', $product->price);
            $product->links = $request->has('links') ? json_encode($request->input('links')) : $product->links;
            $product->reviews = $request->has('reviews') ? json_encode($request->input('reviews')) : $product->reviews;
            $product->parent_post_id = $request->input('parent_post_id', $product->parent_post_id);
            $product->image = $imagePath;
    
            $product->save();
    
            $title = 'Product Updated';
            $action = "The product with the ID of $id was updated.";
            $this->activityService->createActivity($email, $title, $action);
    
            return $this->sendResponse(true, 'Product updated successfully.', ['data' => $product], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while updating the product.', ['error' => $e->getMessage()], 500);
        }
    }
    
    
    public function affiliatePost(Request $request, $productId)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'post_id' => 'required|integer',
            ]);
    
            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }
    
            // Fetch the product
            $product = $this->productService->findProductById($productId);
            if (!$product) {
                return $this->sendResponse(false, 'Product not found', [], 404);
            }
    
            // Decode affiliate posts or initialize as an empty array
            $affiliatePosts = $product->affiliate_posts ? json_decode($product->affiliate_posts, true) : [];
    
            // Check if the post ID already exists
            if (in_array($request->post_id, $affiliatePosts)) {
                return $this->sendResponse(false, 'Post ID already exists in affiliate posts', [], 409);
            }
    
            // Add the new post ID
            $affiliatePosts[] = $request->post_id;
    
            // Update the product
            $product->affiliate_posts = json_encode($affiliatePosts);
            $product->save();
    
            return $this->sendResponse(true, 'Affiliate post added successfully', ['affiliate_posts' => $affiliatePosts], 200);
    
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while adding affiliate post to the product', ['error' => $e->getMessage()], 500);
        }
    }
    

    public function deleteAffiliatePost(Request $request, $productId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'post_id' => 'required|integer', 
            ]);
            
            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }

            $product = $this->productService->findProductById($productId);
            if (!$product) {
                return $this->sendResponse(false, 'Product not found', [], 400);
            }

            $affiliatePosts = $product->affiliate_posts ? json_decode($product->affiliate_posts, true) : [];

            // Check if the post_id exists in the array
            if (!in_array($request->post_id, $affiliatePosts)) {
                return $this->sendResponse(false, 'Post ID not found in affiliate posts', [], 400);
            }

            $affiliatePosts = array_filter($affiliatePosts, function ($id) use ($request) {
                return $id != $request->post_id;
            });

            $product->affiliate_posts = json_encode(array_values($affiliatePosts));
            $product->save();

            return $this->sendResponse(true, 'Affiliate post removed successfully', ['affiliate_posts' => $affiliatePosts], 200);

        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while removing the affiliate post', ['error' => $e->getMessage()], 500);
        }
    }

    public function deleteProduct($id)
    {
        try {
            $product = $this->productService->findProductById($id);
            if (!$product) {
                return $this->sendResponse(false, 'Product not found', [], 400);
            }

            $product->delete();

            return $this->sendResponse(true, 'Product deleted successfully', [], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while deleting the product', ['error' => $e->getMessage()], 500);
        }
    }

}
