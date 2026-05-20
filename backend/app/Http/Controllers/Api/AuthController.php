<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/login',
        summary: 'Dang nhap admin',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'admin123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Dang nhap thanh cong'),
            new OA\Response(response: 422, description: 'Sai thong tin dang nhap'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('admin-api')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/me',
        summary: 'Lay thong tin admin hien tai',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Thong tin user'),
            new OA\Response(response: 401, description: 'Chua dang nhap'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $request->user(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Dang xuat admin',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Dang xuat thanh cong'),
            new OA\Response(response: 401, description: 'Chua dang nhap'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out.',
        ]);
    }

    #[OA\Post(
        path: '/api/register',
        summary: 'Tao tai khoan admin',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Admin 2'),
                    new OA\Property(property: 'email', type: 'string', example: 'admin2@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'admin123456'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'admin123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tao tai khoan thanh cong'),
            new OA\Response(response: 422, description: 'Du lieu khong hop le'),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($data);

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
            ],
        ], 201);
    }
}
