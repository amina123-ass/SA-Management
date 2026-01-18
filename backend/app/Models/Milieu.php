<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\DictionaryTrait;

class Milieu extends Model
{
    use HasFactory, DictionaryTrait;

    protected $table = 'milieux';

    protected $fillable = [
        'libelle',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}