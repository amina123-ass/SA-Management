<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\DictionaryTrait;

class DetailTypeAssistance extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'type_assistance_id',
        'libelle',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function typeAssistance()
    {
        return $this->belongsTo(TypeAssistance::class);
    }
}