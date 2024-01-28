<?php

namespace App\Services\Items;

use App\Models\User;
use App\Models\Product;
use App\Enums\OrderStatus;
use App\Http\Traits\FileTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Requests\ServiceProvider\ProductStoreRequest;
use App\Http\Requests\ServiceProvider\ProductUpdateRequest;

class ProductService
{
    use ApiResponseTrait, FileTrait;
    public function store(User $user, $data)
    {
        DB::beginTransaction();
        try {
            $product = $user->products()->create($data);
            if (isset($data['thumbnail'])) {
                $fileName = $this->uploadFile($data['thumbnail'], $product->attachment_path);
                $product->update(['thumbnail' => $fileName]);
            }
            DB::commit();
            return $this->returnSuccess('Product added successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnWrong($e->getMessage());
        }
    }

    public function update($data, Product $product)
    {
        // Check if this service is under reservation
        if ($product->reservations()->whereRelation('morphReservation', 'status', OrderStatus::PENDING->value)->exists()) {
            return $this->returnWrong('This product is under reservation, you cant edit it!');
        }

        if (isset($data['thumbnail'])) {
            $fileName = $this->uploadFile($data['thumbnail'], $product->attachment_path, $product->thumbnail);
            $data['thumbnail'] = $fileName;
        }
        $product->update($data);
        return $this->returnSuccess('Product data updated successfully');
    }

    public function destroy(Product $product)
    {
        DB::beginTransaction();
        try {
            // Check if this service is under reservation
            if ($product->reservations()->whereRelation('morphReservation', 'status', OrderStatus::PENDING->value)->exists()) {
                return $this->returnWrong('This product is under reservation, you cant delete it!');
            }
            $product->delete();
            $this->removeDirectory($product->attachment_path);
            DB::commit();
            return $this->returnSuccess('Product has been deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnWrong($e->getMessage());
        }
    }
}
