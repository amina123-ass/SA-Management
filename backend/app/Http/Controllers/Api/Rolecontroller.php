<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Liste de tous les rôles
     */
    public function index(Request $request)
    {
        try {
            $query = Role::withCount('users');

            // Filtres
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $roles = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $roles
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir un rôle spécifique
     */
    public function show($id)
    {
        try {
            $role = Role::withCount('users')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $role
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Créer un nouveau rôle
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Le nom est obligatoire',
            'name.unique' => 'Ce nom de rôle existe déjà',
            'display_name.required' => 'Le nom d\'affichage est obligatoire',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::create($request->all());

            AuditLog::createLog('role_created', $role, null, $role->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Rôle créé avec succès',
                'data' => $role
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un rôle
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::findOrFail($id);
            
            // Empêcher la modification du rôle admin_si
            if ($role->name === 'admin_si' && $request->name !== 'admin_si') {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas modifier le nom du rôle Admin SI'
                ], 403);
            }

            $oldData = $role->toArray();
            $role->update($request->all());

            AuditLog::createLog('role_updated', $role, $oldData, $role->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Rôle mis à jour avec succès',
                'data' => $role
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un rôle
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);

            // Empêcher la suppression du rôle admin_si
            if ($role->name === 'admin_si') {
                return response()->json([
                    'success' => false,
                    'message' => 'Le rôle Admin SI ne peut pas être supprimé'
                ], 403);
            }

            // Vérifier si le rôle est utilisé
            if (!$role->canBeDeleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce rôle est attribué à des utilisateurs et ne peut pas être supprimé'
                ], 400);
            }

            AuditLog::createLog('role_deleted', $role, $role->toArray(), null);

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rôle supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un rôle
     */
    public function toggleStatus($id)
    {
        try {
            $role = Role::findOrFail($id);

            // Empêcher la désactivation du rôle admin_si
            if ($role->name === 'admin_si' && $role->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le rôle Admin SI ne peut pas être désactivé'
                ], 403);
            }

            $oldStatus = $role->is_active;
            $role->update([
                'is_active' => !$role->is_active
            ]);

            AuditLog::createLog(
                'role_status_changed',
                $role,
                ['is_active' => $oldStatus],
                ['is_active' => $role->is_active]
            );

            return response()->json([
                'success' => true,
                'message' => $role->is_active ? 'Rôle activé avec succès' : 'Rôle désactivé avec succès',
                'data' => $role
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des permissions disponibles
     */
    public function getAvailablePermissions()
    {
        $permissions = [
            'manage_users' => 'Gérer les utilisateurs',
            'manage_roles' => 'Gérer les rôles',
            'manage_dictionaries' => 'Gérer les dictionnaires',
            'view_audit_logs' => 'Voir les logs d\'audit',
            'access_admin_dashboard' => 'Accéder au dashboard admin',
            'activate_users' => 'Activer les utilisateurs',
            'assign_roles' => 'Attribuer des rôles',
            'reset_passwords' => 'Réinitialiser les mots de passe',
            'view_dossiers' => 'Voir les dossiers',
            'create_dossiers' => 'Créer des dossiers',
            'edit_dossiers' => 'Modifier des dossiers',
            'delete_dossiers' => 'Supprimer des dossiers',
            'validate_dossiers' => 'Valider des dossiers',
            'export_data' => 'Exporter des données',
        ];

        return response()->json([
            'success' => true,
            'data' => $permissions
        ], 200);
    }
}