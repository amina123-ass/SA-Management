<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\DictionaryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::prefix('auth')->group(function () {
    // Inscription et vérification
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmailAndCompleteAccount']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);
    
    // Connexion
    Route::post('/login', [AuthController::class, 'login']);
    
    // Mot de passe oublié
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    // Questions de sécurité
    Route::post('/security-questions', [AuthController::class, 'getSecurityQuestions']);
    Route::post('/verify-security-answers', [AuthController::class, 'verifySecurityAnswersAndResetPassword']);
    Route::get('/security-questions/all', [AuthController::class, 'getAllSecurityQuestions']);
});

// Routes protégées (nécessitent authentification)
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentification
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Routes Admin SI uniquement
    Route::middleware('admin')->prefix('admin')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        
        // Gestion des utilisateurs
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminController::class, 'getUsers']);
            Route::get('/{id}', [AdminController::class, 'getUser']);
            Route::patch('/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
            Route::patch('/{id}/assign-role', [AdminController::class, 'assignRole']);
            Route::post('/{id}/reset-password', [AdminController::class, 'resetUserPassword']);
            Route::patch('/{id}/unlock', [AdminController::class, 'unlockUser']);
            Route::delete('/{id}', [AdminController::class, 'deleteUser']);
        });
        
        // Gestion des rôles
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::get('/permissions', [RoleController::class, 'getAvailablePermissions']);
            Route::get('/{id}', [RoleController::class, 'show']);
            Route::post('/', [RoleController::class, 'store']);
            Route::put('/{id}', [RoleController::class, 'update']);
            Route::delete('/{id}', [RoleController::class, 'destroy']);
            Route::patch('/{id}/toggle-status', [RoleController::class, 'toggleStatus']);
        });
        
        // Gestion des dictionnaires
        Route::prefix('dictionaries')->group(function () {
            Route::get('/{dictionary}', [DictionaryController::class, 'index']);
            Route::get('/{dictionary}/{id}', [DictionaryController::class, 'show']);
            Route::post('/{dictionary}', [DictionaryController::class, 'store']);
            Route::put('/{dictionary}/{id}', [DictionaryController::class, 'update']);
            Route::delete('/{dictionary}/{id}', [DictionaryController::class, 'destroy']);
            Route::patch('/{dictionary}/{id}/toggle-status', [DictionaryController::class, 'toggleStatus']);
        });
        
        // Logs d'audit
        Route::get('/audit-logs', [AdminController::class, 'getAuditLogs']);
    });

    // Routes accessibles à tous les utilisateurs authentifiés
    Route::get('/roles/active', [RoleController::class, 'index'])->where('is_active', 1);
    
    // Dictionnaires publics (lecture seule pour utilisateurs authentifiés)
    Route::get('/dictionaries/{dictionary}/active', [DictionaryController::class, 'index'])->where('is_active', 1);
});

// Route de test
Route::get('/test', function () {
    return response()->json([
        'message' => 'SA Management API is running',
        'version' => '1.0.0',
        'timestamp' => now()
    ]);
});

// Add this at the end, before the closing brace
Route::middleware('auth:sanctum')->get('/debug/user', function () {
    $user = auth()->user();
    return response()->json([
        'user' => $user,
        'role' => $user->role,
        'is_admin' => $user->role && $user->role->name === 'admin_si',
    ]);
});