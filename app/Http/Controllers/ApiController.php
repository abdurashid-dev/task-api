<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function index(Request $request)
    {
//        $quantityProduct = (object)[
//            [
//                'id' => 1,
//                'quantity' => 30
//            ],
//            [
//                'id' => 2,
//                'quantity' => 20
//            ],
//        ];
        $materialsNumber = [];
        $result = [];
        $BaseWarehouse = [];
        foreach ($request->products as $product) {
            $data = Product::find($product['id']);
            $resultArray = [
                'product_name' => $data->name,
                'product_qty' => $product['quantity'],
            ];
            $product_materials = Product::join('product_materials', 'products.id', 'product_materials.product_id')
                ->join('materials', 'materials.id', 'product_materials.material_id')
                ->select('products.id as product_id', 'materials.name as material_name', 'materials.id as material_id', DB::raw('product_materials.quantity * ' . $product['quantity'] . ' as quantity'), 'products.name as product_name')
                ->where('products.id', $product['id'])
                ->get();
            $pr_materials = [];
            foreach ($product_materials as $product_material) {
                $warehouses = Warehouse::where('material_id', $product_material->material_id)
                    ->join('materials', 'materials.id', '=', 'warehouses.material_id')
                    ->select('warehouses.*', 'materials.name as material_name')
                    ->get();
                foreach ($warehouses as $warehouse) {
                    if (!isset($BaseWarehouse[$warehouse->id])){
                        $BaseWarehouse[$warehouse->id] = $warehouse->remainder;
                    }
                }
                $warehousesArray = [];
                foreach ($warehouses as $warehouse) {
                    if ($BaseWarehouse[$warehouse->id] == 0){
                        continue;
                    }
                    $quantity = $BaseWarehouse[$warehouse->id] - (int)$product_material->quantity;
                    if ($quantity < 0) {
                        $product_material->quantity = abs($quantity);
                        $warehousesArray[] = [
                            'warehouse_id' => $warehouse->id,
                            'material_name' => $warehouse->material_name,
                            'qty' => $BaseWarehouse[$warehouse->id],
                            'price' => $warehouse->price,
                        ];
                        $BaseWarehouse[$warehouse->id] = 0;
                    } else {
                        $warehousesArray[] = [
                            'warehouse_id' => $warehouse->id,
                            'material_name' => $warehouse->material_name,
                            'qty' => round($product_material->quantity),
                            'price' => $warehouse->price,
                        ];
                        $BaseWarehouse[$warehouse->id] = $quantity;
                        break;
                    }
                    $i = 0;
                    foreach ($warehouses as $whouse) {
                        $i += $BaseWarehouse[$whouse->id];
                    }
                    if ($i == 0) {
                        $warehousesArray[] = [
                            'warehouse_id' => null,
                            'material_name' => $warehouse->material_name,
                            'qty' => $product_material->quantity,
                            'price' => null,
                        ];
                    }

                }
                $pr_materials = array_merge($pr_materials, $warehousesArray);
            }
            $resultArray['product_materials'] = $pr_materials;
            $result[] = $resultArray;
//            dd($BaseWarehouse);
        }
        return response()->json([
            'result' => $result,
        ]);
    }
}
