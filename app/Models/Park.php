<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Geometry;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Park extends Model
{
    use HasSpatial;

    protected $casts = [
        'geometry' => Geometry::class,
    ];
}
