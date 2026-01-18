<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EtatDossier;
use App\Models\NatureDon;
use App\Models\TypeAssistance;
use App\Models\DetailTypeAssistance;
use App\Models\EtatDon;
use App\Models\Commune;
use App\Models\Milieu;
use App\Models\SecurityQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DictionaryController extends Controller
{
    private function getModel($dictionary)
    {
        $models = [
            'etat_dossiers' => EtatDossier::class,
            'nature_dons' => NatureDon::class,
            'type_assistances' => TypeAssistance::class,
            'detail_type_assistances' => DetailTypeAssistance::class,
            'etat_dons' => EtatDon::class,
            'communes' => Commune::class,
            'milieux' => Milieu::class,
            'security_questions' => SecurityQuestion::class,
        ];

        if (!isset($models[$dictionary])) {
            return null;
        }

        return new $models[$dictionary];
    }

    private function getValidationRules($dictionary, $isUpdate = false)
    {
        $rules = [];

        switch ($dictionary) {
            case 'etat_dossiers':
            case 'etat_dons':
                $rules = [
                    'libelle' => 'required|string|max:255',
                    'code' => ($isUpdate ? 'sometimes|' : '') . 'required|string|max:50|unique:' . $dictionary . ',code',
                    'is_active' => 'boolean',
                ];
                break;

            case 'communes':
                $rules = [
                    'nom' => 'required|string|max:255',
                    'code' => ($isUpdate ? 'sometimes|' : '') . 'required|string|max:50|unique:communes,code',
                    'is_active' => 'boolean',
                ];
                break;

            case 'nature_dons':
            case 'type_assistances':
            case 'milieux':
                $rules = [
                    'libelle' => 'required|string|max:255',
                    'is_active' => 'boolean',
                ];
                break;

            case 'detail_type_assistances':
                $rules = [
                    'type_assistance_id' => 'required|exists:type_assistances,id',
                    'libelle' => 'required|string|max:255',
                    'is_active' => 'boolean',
                ];
                break;

            case 'security_questions':
                $rules = [
                    'question' => 'required|string|max:500',
                    'is_active' => 'boolean',
                ];
                break;
        }

        return $rules;
    }

    public function index($dictionary, Request $request)
    {
        try {
            Log::info('DictionaryController::index called', [
                'dictionary' => $dictionary,
                'params' => $request->all()
            ]);

            $model = $this->getModel($dictionary);

            if (!$model) {
                Log::error('Invalid dictionary type', ['dictionary' => $dictionary]);
                return response()->json([
                    'success' => false,
                    'message' => 'Type de dictionnaire invalide'
                ], 404);
            }

            $query = $model->newQuery();

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($dictionary === 'detail_type_assistances') {
                if (method_exists($model, 'typeAssistance')) {
                    $query->with('typeAssistance');
                }
                
                if ($request->has('type_assistance_id')) {
                    $query->where('type_assistance_id', $request->type_assistance_id);
                }
            }

            $query->orderBy('id', 'desc');

            $items = $query->get();

            Log::info('Items retrieved successfully', [
                'dictionary' => $dictionary,
                'count' => $items->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $items
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in DictionaryController::index', [
                'dictionary' => $dictionary,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    public function show($dictionary, $id)
    {
        try {
            $model = $this->getModel($dictionary);

            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de dictionnaire invalide'
                ], 404);
            }

            $item = $model->find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Élément non trouvé'
                ], 404);
            }

            if ($dictionary === 'detail_type_assistances' && method_exists($item, 'typeAssistance')) {
                $item->load('typeAssistance');
            }

            return response()->json([
                'success' => true,
                'data' => $item
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in DictionaryController::show', [
                'dictionary' => $dictionary,
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    public function store($dictionary, Request $request)
    {
        try {
            $model = $this->getModel($dictionary);

            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de dictionnaire invalide'
                ], 404);
            }

            $rules = $this->getValidationRules($dictionary);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $item = $model->create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Élément créé avec succès',
                'data' => $item
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error in DictionaryController::store', [
                'dictionary' => $dictionary,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    public function update($dictionary, $id, Request $request)
    {
        try {
            $model = $this->getModel($dictionary);

            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de dictionnaire invalide'
                ], 404);
            }

            $item = $model->find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Élément non trouvé'
                ], 404);
            }

            $rules = $this->getValidationRules($dictionary, true);

            if (isset($rules['code'])) {
                $rules['code'] = str_replace(',code', ',code,' . $id, $rules['code']);
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $item->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Élément mis à jour avec succès',
                'data' => $item
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in DictionaryController::update', [
                'dictionary' => $dictionary,
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    public function destroy($dictionary, $id)
    {
        try {
            $model = $this->getModel($dictionary);

            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de dictionnaire invalide'
                ], 404);
            }

            $item = $model->find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Élément non trouvé'
                ], 404);
            }

            if (method_exists($item, 'canBeDeleted') && !$item->canBeDeleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élément ne peut pas être supprimé car il est utilisé'
                ], 400);
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Élément supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in DictionaryController::destroy', [
                'dictionary' => $dictionary,
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    public function toggleStatus($dictionary, $id)
    {
        try {
            $model = $this->getModel($dictionary);

            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de dictionnaire invalide'
                ], 404);
            }

            $item = $model->find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Élément non trouvé'
                ], 404);
            }

            $item->update([
                'is_active' => !$item->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Statut modifié avec succès',
                'data' => $item
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in DictionaryController::toggleStatus', [
                'dictionary' => $dictionary,
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }
}