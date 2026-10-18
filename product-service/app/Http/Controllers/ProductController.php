<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;
class ProductController extends Controller {
 private function data(Request $request): array {
  return $this->validate($request, [
   'name'=>'required|string|max:255', 'description'=>'nullable|string',
   'price'=>['required','numeric','min:0','max:99999999.99','regex:/^\\d+(\\.\\d{1,2})?$/'],
   'quantity'=>'required|integer|min:0|max:2147483647',
  ]);
 }
 public function index() { return response()->json(['data'=>Product::orderBy('id')->get()]); }
 public function show($id) { return response()->json(['data'=>Product::findOrFail($id)]); }
 public function store(Request $request) { return response()->json(['data'=>Product::create($this->data($request))],201); }
 public function update(Request $request,$id) {
  $product=Product::findOrFail($id); $product->fill($this->data($request)); $product->save();
  return response()->json(['data'=>$product]);
 }
 public function destroy($id) { Product::findOrFail($id)->delete(); return response('',204); }
}
