<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Trait pour les fonctionnalités communes des dictionnaires
trait DictionaryTrait
{
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function canBeDeleted(): bool
    {
        return true; // À override dans les modèles qui ont des relations
    }
}

// État Dossier
class EtatDossier extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'libelle_fr',
        'libelle_ar',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

// Nature Don
class NatureDon extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'libelle_fr',
        'libelle_ar',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

// Type Assistance
class TypeAssistance extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'libelle_fr',
        'libelle_ar',
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

// Détail Type Assistance
class DetailTypeAssistance extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'type_assistance_id',
        'libelle_fr',
        'libelle_ar',
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

// État Don
class EtatDon extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'libelle_fr',
        'libelle_ar',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

// Commune
class Commune extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'nom_fr',
        'nom_ar',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

// Milieu
class Milieu extends Model
{
    use HasFactory, DictionaryTrait;

    protected $fillable = [
        'libelle_fr',
        'libelle_ar',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}