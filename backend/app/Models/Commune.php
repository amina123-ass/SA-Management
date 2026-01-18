<?php
// Commune.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\DictionaryTrait;

class Commune extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'nom',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}