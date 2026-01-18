<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log pour debug
        Log::info('AdminMiddleware: Request received', [
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        if (!auth()->check()) {
            Log::warning('AdminMiddleware: User not authenticated');
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $user = auth()->user();
        
        Log::info('AdminMiddleware: User authenticated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_role' => $user->role !== null,
            'role_name' => $user->role?->name ?? 'N/A',
        ]);

        // TEMPORAIRE: Bypass pour debug - RETIREZ CECI EN PRODUCTION!
        // Décommentez la ligne suivante pour bypass le check admin temporairement
        // return $next($request);

        // Vérifier si l'utilisateur a le rôle admin_si
        if (!$user->role) {
            Log::warning('AdminMiddleware: User has no role assigned', [
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Aucun rôle attribué.'
            ], 403);
        }

        if ($user->role->name !== 'admin_si') {
            Log::warning('AdminMiddleware: User does not have admin role', [
                'user_id' => $user->id,
                'role' => $user->role->name,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Réservé aux administrateurs SI.'
            ], 403);
        }

        // Vérifier si le compte est actif
        if (!$user->is_active) {
            Log::warning('AdminMiddleware: User account is inactive', [
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé'
            ], 403);
        }

        Log::info('AdminMiddleware: Access granted', [
            'user_id' => $user->id,
        ]);

        return $next($request);
    }
}