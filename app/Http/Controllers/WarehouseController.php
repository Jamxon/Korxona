<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Product;
use App\Models\Product_material;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use Illuminate\Support\Facades\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Warehouse::all();
    }

    public function updateWarehouse(StoreWarehouseRequest $request)
    {
        $productName = $request->name;
        $productQuantity = $request->quantity;

        $product = Product::where('product_name', $productName)->first();
        $productMaterial = Product_material::leftJoin('products', 'products.id', '=', 'product_materials.product_id')
            ->leftJoin('materials', 'materials.id', '=', 'product_materials.material_id')
            ->where('product_id', $product->id)->get();

        $remainingMaterials = [];

        foreach ($productMaterial as $material) {
            $material->quantity = $material->quantity * $productQuantity;
            $remainingMaterials[$material->material_id] = $material->quantity;
        }

        $warehouse = Warehouse::all();

        foreach ($warehouse as $warehouseItem) {
            $materialId = $warehouseItem->material_id;

            if (isset($remainingMaterials[$materialId])) {
                $neededQuantity = $remainingMaterials[$materialId];

                if (!$warehouseItem->remainder < $neededQuantity) {
                    return response()->json([
                        'message' => 'Not enough materials in the warehouse'
                    ], 400);
                } else {
                    $warehouseItem->remainder = $warehouseItem->remainder - $neededQuantity;
                }
            }
        }

        return response()->json([
            'message' => 'Warehouse updated successfully'
        ], 200);
    }



    public function getProductionInfo()
    {
        // Define the products to be produced
        $productsToProduce = [
            ['name' => "Ko'ylak", 'quantity' => 30],
            ['name' => 'Shim', 'quantity' => 20],
        ];

        $result = [];

        foreach ($productsToProduce as $productInfo) {
            $product = Product::where('product_name', $productInfo['name'])->first();

            if ($product) {
                $requiredMaterials = $this->calculateRequiredMaterials($product, $productInfo['quantity']);

                $materialsInfo = $this->checkWarehouseStock($requiredMaterials);

                $result[] = [
                    'product_name' => $product->product_name,
                    'product_qty' => $productInfo['quantity'],
                    'product_materials' => $materialsInfo,
                ];
            }
        }

        return response()->json(['result' => $result]);
    }

    private function calculateRequiredMaterials(Product $product, $quantity)
    {
        $requiredMaterials = [];

        $productMaterials = Product_material::where('product_id', $product->id)->get();

        foreach ($productMaterials as $productMaterial) {
            $requiredMaterials[] = [
                'material_id' => $productMaterial->material_id,
                'quantity' => $productMaterial->quantity * $quantity,
            ];
        }

        return $requiredMaterials;
    }

    private function checkWarehouseStock($requiredMaterials)
    {
        $materialsInfo = [];

        try {
            \DB::beginTransaction();

            foreach ($requiredMaterials as $requiredMaterial) {
                $material = Material::find($requiredMaterial['material_id']);

                if ($material) {
                    $warehouse = Warehouse::where('material_id', $material->id)->first();
                    if ($warehouse) {
                        $availableQuantity = max(0, $warehouse->remainder - $this->calculateReservedQuantity($material->id));

                        $actualQuantity = min($requiredMaterial['quantity'], $availableQuantity);

                        $this->updateReservedQuantity($material->id, $actualQuantity);

                        $materialsInfo[] = [
                            'warehouse_id' => $warehouse->id,
                            'material_name' => $material->materials_name,
                            'qty' => $warehouse->remainder,
                            'price' => $warehouse->price,
                        ];
                    } else {
                        \Log::error('Warehouse not found for material ID: ' . $material->id);
                    }
                }
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Exception: ' . $e->getMessage());
        }

        return $materialsInfo;
    }


    private function calculateReservedQuantity($materialId)
    {
        return Product_material::where('material_id', $materialId)->value('quantity');
    }

    private function updateReservedQuantity($materialId, $quantity)
    {
        Material::where('id', $materialId)->increment('reserved_quantity', $quantity);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWarehouseRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        //
    }
}
