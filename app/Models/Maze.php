<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maze extends Model
{
    use HasFactory;

    protected $maps = [
        'gridSize' => 'grid_size',
    ];
}
