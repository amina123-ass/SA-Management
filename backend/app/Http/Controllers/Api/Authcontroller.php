<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerification;
use App\Mail\PasswordResetMail;
use App\Models\SecurityQuestion;
use App\Models\User;
use App\Models\UserSecurityAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Inscription avec envoi d'email
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telephone' => 'required|string|max:20',
            'adresse' => 'required|string|max:500',
        ], [
            'nom.required' => 'Le nom est obligatoire',
            'prenom.required' => 'Le prénom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'telephone.required' => 'Le téléphone est obligatoire',
            'adresse.required' => 'L\'adresse est obligatoire',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Créer l'utilisateur
            $user = User::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'email_verified_at' => null,
                'is_active' => false,
                'role_id' => null,
            ]);

            // Générer le token de vérification
            $verificationToken = Str::random(64);
            
            // Stocker le token
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($verificationToken),
                    'created_at' => now()
                ]
            );

            // Envoyer l'email de vérification
            try {
                Mail::to($user->email)->send(new EmailVerification($user, $verificationToken));
                $emailSent = true;
            } catch (\Exception $e) {
                // Si l'envoi échoue, on continue quand même (pour le debug)
                $emailSent = false;
                \Log::error('Erreur envoi email: ' . $e->getMessage());
            }

            DB::commit();

            // Pour faciliter le debug, on retourne aussi le lien
            $verificationUrl = config('app.frontend_url', 'http://localhost:3000') . '/verify-email?email=' . urlencode($user->email) . '&token=' . $verificationToken;

            return response()->json([
                'success' => true,
                'message' => $emailSent 
                    ? 'Inscription réussie! Vérifiez votre email pour activer votre compte.' 
                    : 'Inscription réussie! Utilisez le lien ci-dessous (email non envoyé).',
                'email_sent' => $emailSent,
                'verification_url' => $verificationUrl, // Pour debug
                'data' => [
                    'email' => $user->email
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renvoyer l'email de vérification
     */
    public function resendVerificationEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if ($user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet email est déjà vérifié'
                ], 400);
            }

            // Générer un nouveau token
            $verificationToken = Str::random(64);
            
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($verificationToken),
                    'created_at' => now()
                ]
            );

            // Envoyer l'email
            Mail::to($user->email)->send(new EmailVerification($user, $verificationToken));

            return response()->json([
                'success' => true,
                'message' => 'Email de vérification renvoyé avec succès'
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
     * Obtenir toutes les questions de sécurité disponibles
     */
    public function getAllSecurityQuestions()
    {
        try {
            $questions = SecurityQuestion::where('is_active', true)->get();

            return response()->json([
                'success' => true,
                'data' => $questions
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier l'email et finaliser le compte
     */
    public function verifyEmailAndCompleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
            'security_answers' => 'required|array|min:3|max:3',
            'security_answers.*.question_id' => 'required|exists:security_questions,id',
            'security_answers.*.answer' => 'required|string|min:2',
        ], [
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'security_answers.required' => 'Vous devez répondre à 3 questions de sécurité',
            'security_answers.min' => 'Vous devez répondre à exactement 3 questions',
            'security_answers.max' => 'Vous devez répondre à exactement 3 questions',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            // Vérifier le token
            $tokenRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$tokenRecord || !Hash::check($request->token, $tokenRecord->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de vérification invalide ou expiré'
                ], 400);
            }

            // Vérifier que le token n'a pas expiré (24h)
            if (now()->diffInHours($tokenRecord->created_at) > 24) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le lien de vérification a expiré'
                ], 400);
            }

            // Vérifier que les 3 questions sont différentes
            $questionIds = array_column($request->security_answers, 'question_id');
            if (count($questionIds) !== count(array_unique($questionIds))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez sélectionner 3 questions de sécurité différentes'
                ], 422);
            }

            // Mettre à jour le mot de passe et vérifier l'email
            $user->update([
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);

            // Enregistrer les réponses aux questions de sécurité
            foreach ($request->security_answers as $answer) {
                DB::table('user_security_answers')->insert([
                    'user_id' => $user->id,
                    'security_question_id' => $answer['question_id'],
                    'answer_hash' => Hash::make(strtolower(trim($answer['answer']))),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Supprimer le token
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Email vérifié et compte finalisé avec succès. Votre compte sera activé par un administrateur.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connexion
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            // Vérifications
            if (!$user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez vérifier votre email avant de vous connecter'
                ], 403);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte n\'est pas encore activé. Veuillez contacter l\'administrateur.'
                ], 403);
            }

            if (!$user->role_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun rôle n\'a été attribué à votre compte. Veuillez contacter l\'administrateur.'
                ], 403);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            // Créer le token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'nom' => $user->nom,
                        'prenom' => $user->prenom,
                        'email' => $user->email,
                        'telephone' => $user->telephone,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
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
     * Obtenir l'utilisateur connecté
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user()->load('role');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                    'adresse' => $user->adresse,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'email_verified_at' => $user->email_verified_at,
                ]
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
     * Mot de passe oublié - Méthode 1: Par email
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            // Générer le token
            $token = Str::random(64);

            // Stocker le token
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Envoyer l'email
            Mail::to($user->email)->send(new PasswordResetMail($user, $token));

            return response()->json([
                'success' => true,
                'message' => 'Un email de réinitialisation a été envoyé à votre adresse'
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
     * Réinitialiser le mot de passe par email
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
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
            DB::beginTransaction();

            $tokenRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$tokenRecord || !Hash::check($request->token, $tokenRecord->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou expiré'
                ], 400);
            }

            // Vérifier l'expiration (1h)
            if (now()->diffInHours($tokenRecord->created_at) > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le lien de réinitialisation a expiré'
                ], 400);
            }

            $user = User::where('email', $request->email)->first();
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Supprimer tous les tokens de ce user
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            // Révoquer tous les tokens d'accès
            $user->tokens()->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les questions de sécurité d'un utilisateur
     */
    public function getSecurityQuestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            $securityAnswers = UserSecurityAnswer::where('user_id', $user->id)
                ->with('securityQuestion')
                ->get();

            if ($securityAnswers->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune question de sécurité configurée pour cet utilisateur'
                ], 404);
            }

            $questions = $securityAnswers->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'question_id' => $answer->security_question_id,
                    'question_fr' => $answer->securityQuestion->question_fr,
                    'question_ar' => $answer->securityQuestion->question_ar,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $questions
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
     * Vérifier les réponses aux questions de sécurité et réinitialiser le mot de passe
     */
    public function verifySecurityAnswersAndResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'answers' => 'required|array|min:3',
            'answers.*.id' => 'required|exists:user_security_answers,id',
            'answers.*.answer' => 'required|string',
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
            DB::beginTransaction();

            $user = User::where('email', $request->email)->first();

            // Vérifier toutes les réponses
            $allCorrect = true;
            foreach ($request->answers as $answerData) {
                $userAnswer = UserSecurityAnswer::find($answerData['id']);
                
                if (!$userAnswer || $userAnswer->user_id !== $user->id) {
                    $allCorrect = false;
                    break;
                }

                if (!Hash::check(strtolower(trim($answerData['answer'])), $userAnswer->answer_hash)) {
                    $allCorrect = false;
                    break;
                }
            }

            if (!$allCorrect) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une ou plusieurs réponses sont incorrectes'
                ], 401);
            }

            // Réinitialiser le mot de passe
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Révoquer tous les tokens
            $user->tokens()->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}