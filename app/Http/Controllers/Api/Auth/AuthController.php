<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Auth para clientes de la tienda. Usa Sanctum personal access tokens.
 *
 * POST /api/auth/register   crea cuenta + token
 * POST /api/auth/login      valida credenciales + token
 * POST /api/auth/logout     revoca el token actual (auth:sanctum)
 * GET  /api/auth/me         devuelve el user del token (auth:sanctum)
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => 'cliente',
        ]);

        $token = $user->createToken('shop')->plainTextToken;

        return response()->json([
            'user' => $this->shapeUser($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Registro para usuarios del CMS (cargos de la empresa). Requiere
     * código de invitación que se valida contra la variable de entorno
     * AVAX_ADMIN_INVITE_CODE (default: AVAX2026).
     */
    public function adminRegister(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'cargo' => ['required', 'string', 'max:120'],
            'admin_code' => ['required', 'string'],
        ]);

        $expected = env('AVAX_ADMIN_INVITE_CODE', 'AVAX2026');
        if (!hash_equals($expected, $data['admin_code'])) {
            throw ValidationException::withMessages([
                'admin_code' => ['Código de empresa inválido.'],
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'cargo' => $data['cargo'],
        ]);

        $token = $user->createToken('admin')->plainTextToken;

        return response()->json([
            'user' => $this->shapeUser($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Login para el CMS. Mismo endpoint que login normal pero rechaza
     * cuentas con role distinto de 'admin'.
     */
    public function adminLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', mb_strtolower($data['email']))->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        if ($user->role !== 'admin') {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta no tiene acceso al panel administrativo.'],
            ]);
        }

        $token = $user->createToken('admin')->plainTextToken;

        return response()->json([
            'user' => $this->shapeUser($user),
            'token' => $token,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', mb_strtolower($data['email']))->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        $token = $user->createToken('shop')->plainTextToken;

        return response()->json([
            'user' => $this->shapeUser($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->shapeUser($request->user()),
        ]);
    }

    private function shapeUser(User $u): array
    {
        return [
            'id' => (string) $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role ?? 'cliente',
            'cargo' => $u->cargo,
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
}
