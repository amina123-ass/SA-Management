<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\DictionaryTrait;

class NatureDon extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'libelle',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}