<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\DictionaryTrait;

class TypeAssistance extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'libelle',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function details()
    {
        return $this->hasMany(DetailTypeAssistance::class);
    }

    public function canBeDeleted(): bool
    {
        return $this->details()->count() === 0;
    }
}