<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * Dashboard statistiques
     */
    public function dashboard()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
                'inactive_users' => User::inactive()->count(),
                'pending_activation' => User::verified()->inactive()->count(),
                'unverified_users' => User::unverified()->count(),
                'users_with_role' => User::withRole()->count(),
                'users_without_role' => User::verified()->withoutRole()->count(),
                'roles_distribution' => User::select('role_id', DB::raw('count(*) as count'))
                    ->whereNotNull('role_id')
                    ->groupBy('role_id')
                    ->with('role:id,display_name')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'role_name' => $item->role->display_name ?? 'N/A',
                            'count' => $item->count
                        ];
                    }),
                'recent_activities' => AuditLog::with('user')
                    ->latest()
                    ->take(10)
                    ->get()
                    ->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'action' => $log->action,
                            'user' => $log->user ? $log->user->full_name : 'Système',
                            'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
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
     * Liste des utilisateurs avec filtres et recherche
     */
    public function getUsers(Request $request)
    {
        try {
            $query = User::with('role');

            // Recherche
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                });
            }

            // Filtres
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->has('email_verified')) {
                if ($request->email_verified) {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }

            if ($request->has('role_id')) {
                if ($request->role_id === 'null') {
                    $query->whereNull('role_id');
                } else {
                    $query->where('role_id', $request->role_id);
                }
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users
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
     * Obtenir un utilisateur spécifique
     */
    public function getUser($id)
    {
        try {
            $user = User::with(['role', 'securityAnswers.securityQuestion'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleUserStatus($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Empêcher de désactiver son propre compte
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas désactiver votre propre compte'
                ], 403);
            }

            $oldStatus = $user->is_active;
            $user->update([
                'is_active' => !$user->is_active
            ]);

            AuditLog::createLog(
                'user_status_changed',
                $user,
                ['is_active' => $oldStatus],
                ['is_active' => $user->is_active]
            );

            return response()->json([
                'success' => true,
                'message' => $user->is_active ? 'Utilisateur activé avec succès' : 'Utilisateur désactivé avec succès',
                'data' => $user
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
     * Assigner un rôle à un utilisateur
     */
    public function assignRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($id);
            
            // Empêcher de modifier son propre rôle
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas modifier votre propre rôle'
                ], 403);
            }

            $oldRoleId = $user->role_id;
            $user->update([
                'role_id' => $request->role_id
            ]);

            AuditLog::createLog(
                'user_role_changed',
                $user,
                ['role_id' => $oldRoleId],
                ['role_id' => $user->role_id]
            );

            return response()->json([
                'success' => true,
                'message' => 'Rôle assigné avec succès',
                'data' => $user->load('role')
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
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetUserPassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($id);

            $user->update([
                'password' => Hash::make($request->password),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            // Révoquer tous les tokens de l'utilisateur
            $user->tokens()->delete();

            AuditLog::createLog('admin_password_reset', $user);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès'
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
     * Supprimer un utilisateur
     */
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);

            // Empêcher de supprimer son propre compte
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte'
                ], 403);
            }

            AuditLog::createLog('user_deleted', $user, $user->toArray(), null);

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès'
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
     * Déverrouiller un utilisateur
     */
    public function unlockUser($id)
    {
        try {
            $user = User::findOrFail($id);

            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            AuditLog::createLog('user_unlocked', $user);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur déverrouillé avec succès',
                'data' => $user
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
     * Obtenir les logs d'audit
     */
    public function getAuditLogs(Request $request)
    {
        try {
            $query = AuditLog::with('user');

            // Filtres
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('action')) {
                $query->where('action', 'like', "%{$request->action}%");
            }

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Tri
            $query->latest();

            // Pagination
            $perPage = $request->get('per_page', 20);
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $logs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}