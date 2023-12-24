<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'product_code',
    ];

    public function materials()
    {
        return $this->hasManyThrough(
            Material::class,
            Product_material::class,
            'product_id',
            'id',
            'id',
            'material_id'
        );
    }
}
