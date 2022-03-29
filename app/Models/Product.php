<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use phpDocumentor\Reflection\Types\This;

/**
 * @method static join(string $string, string $string1, string $string2)
 */
class Product extends Model
{
    use HasFactory;

    public function materials(): hasMany
    {
        return $this->hasMany(Product_material::class);
    }
}
