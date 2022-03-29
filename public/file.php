<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

//use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function index(Request $request)
    {
        $quantityProduct = $request->products;
        $materialsNumber = [];
        $result = [];
        foreach ($quantityProduct as $product) {
            $data = Product::find($product['id']);
            $resultArray = [
                'product_name' => $data->name,
                'product_qty' => $product['quantity'],
            ];
            $product_materials = Product::join('product_materials', 'products.id', '=', 'product_materials.product_id')
                ->join('materials', 'materials.id', '=', 'product_materials.material_id')
                ->select('products.id as product_id', 'materials.name as material_name', 'materials.id as material_id', DB::raw('product_materials.quantity * ' . $product['quantity'] . ' as quantity'), 'products.name as product_name')
                ->where('products.id', $product['id'])
                ->get();
            $pr_materials = [];
            foreach ($product_materials as $product_material) {
                $warehouses = Warehouse::where('material_id', $product_material->material_id)
                    ->join('materials', 'materials.id', '=', 'warehouses.material_id')
                    ->select('warehouses.*', 'materials.name as material_name')
                    ->get();
                $total_remainder = $warehouses->sum('remainder');
                if (!isset($materialsNumber[$product_material->material_id])) {
                    $materialsNumber[$product_material->material_id] = $total_remainder;
                }
                $warehousesArray = [];
                foreach ($warehouses as $warehouse){
                    if ($materialsNumber[$product_material->material_id] != 0) {
                        $res = $materialsNumber[$product_material->material_id] - (int)$product_material->quantity;
                        if ($res <= 0){
                            if ((int)$product_material->quantity == abs($res)){
                                $warehousesArray[] = [
                                    'warehouse_id' => null,
                                    'material_name' => $warehouse->material_name,
                                    'qty' => $product_material->quantity,
                                    'price' => null,
                                ];
                            }elseif ((int)$product_material->quantity < abs($res)){
                                $warehousesArray[] = [
                                    'warehouse_id' => $warehouse->id,
                                    'material_name' => $warehouse->material_name,
                                    'qty' => $res,
                                    'price' => $warehouse->price,
                                ];
                            }elseif ((int)$product_material->quantity > abs($res)){
                                $warehousesArray[] = [
                                    'warehouse_id' => null,
                                    'material_name' => $warehouse->material_name,
                                    'qty' => $product_material->quantity,
                                    'price' => null,
                                ];
                            }
                            $product_material->quantity = abs($res);
                        }else{
                            $quantity = $warehouse->remainder - (int)$product_material->quantity;
                            if ($quantity < 0) {
                                $product_material->quantity = abs($quantity);
                                $warehousesArray[] = [
                                    'warehouse_id' => $warehouse->id,
                                    'material_name' => $warehouse->material_name,
                                    'qty' => $warehouse->remainder,
                                    'price' => $warehouse->price,
                                ];
                                $materialsNumber[$product_material->material_id] = $materialsNumber[$product_material->material_id] - $warehouse->remainder;
                            }else{
                                $warehousesArray[] = [
                                    'warehouse_id' => $warehouse->id,
                                    'material_name' => $warehouse->material_name,
                                    'qty' => $product_material->quantity,
                                    'price' => $warehouse->price,
                                ];
                                $materialsNumber[$product_material->material_id] = $materialsNumber[$product_material->material_id] - $product_material->quantity;
                                break;
                            }
                        }
                    }

                }
                $pr_materials = array_merge($pr_materials, $warehousesArray);
            }
            $resultArray['product_materials'] = $pr_materials;
            $result[] = $resultArray;
        }
        return response()->json([
            'result' => $result,
        ]);
    }
}
