<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionMajor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdmissionMajorController extends Controller
{
    #[OA\Get(
        path: '/api/admission-majors',
        summary: 'Danh sach nganh tuyen sinh',
        security: [['sanctum' => []]],
        tags: ['Admission Majors'],
        responses: [new OA\Response(response: 200, description: 'Danh sach nganh')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $search = trim((string) $request->query('q', ''));

        $query = AdmissionMajor::query()
            ->orderByDesc('year')
            ->orderBy('major_name');

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $like = "%{$search}%";
                $sub->where('major_name', 'like', $like)
                    ->orWhere('major_code', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate($perPage),
        ]);
    }

    #[OA\Get(
        path: '/api/admission-majors/{admission_major}',
        summary: 'Chi tiet nganh tuyen sinh',
        security: [['sanctum' => []]],
        tags: ['Admission Majors'],
        parameters: [new OA\Parameter(name: 'admission_major', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Chi tiet nganh')]
    )]
    public function show(AdmissionMajor $admissionMajor): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $admissionMajor,
        ]);
    }

    #[OA\Post(
        path: '/api/admission-majors',
        summary: 'Tao nganh tuyen sinh',
        security: [['sanctum' => []]],
        tags: ['Admission Majors'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['major_name'],
                properties: [
                    new OA\Property(property: 'year', type: 'integer', example: 2026),
                    new OA\Property(property: 'major_name', type: 'string', example: 'Cong nghe thong tin'),
                    new OA\Property(property: 'major_code', type: 'string', example: '7480201'),
                    new OA\Property(property: 'subject_groups', type: 'array', items: new OA\Items(type: 'string'), example: ['A00', 'A01']),
                    new OA\Property(property: 'quota', type: 'integer', example: 120),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Tao thanh cong')]
    )]
    public function store(Request $request): JsonResponse
    {
        $admissionMajor = AdmissionMajor::create($this->validatedData($request));

        return response()->json([
            'status' => 'success',
            'data' => $admissionMajor,
        ], 201);
    }

    #[OA\Put(
        path: '/api/admission-majors/{admission_major}',
        summary: 'Cap nhat nganh tuyen sinh',
        security: [['sanctum' => []]],
        tags: ['Admission Majors'],
        parameters: [new OA\Parameter(name: 'admission_major', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'major_name', type: 'string', example: 'Cong nghe thong tin'),
            new OA\Property(property: 'quota', type: 'integer', example: 150),
        ])),
        responses: [new OA\Response(response: 200, description: 'Cap nhat thanh cong')]
    )]
    public function update(Request $request, AdmissionMajor $admissionMajor): JsonResponse
    {
        $admissionMajor->update($this->validatedData($request, true));

        return response()->json([
            'status' => 'success',
            'data' => $admissionMajor->refresh(),
        ]);
    }

    #[OA\Delete(
        path: '/api/admission-majors/{admission_major}',
        summary: 'Xoa nganh tuyen sinh',
        security: [['sanctum' => []]],
        tags: ['Admission Majors'],
        parameters: [new OA\Parameter(name: 'admission_major', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Xoa thanh cong')]
    )]
    public function destroy(AdmissionMajor $admissionMajor): JsonResponse
    {
        $admissionMajor->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Admission major deleted.',
        ]);
    }

    private function validatedData(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'nullable';

        return $request->validate([
            'year' => [$required, 'nullable', 'integer', 'between:2000,2100'],
            'major_name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'major_code' => [$required, 'nullable', 'string', 'max:255'],
            'subject_groups' => [$required, 'nullable', 'array'],
            'score_thpt' => [$required, 'nullable', 'numeric', 'between:0,40'],
            'score_hoc_ba' => [$required, 'nullable', 'numeric', 'between:0,40'],
            'score_dgnl' => [$required, 'nullable', 'numeric', 'between:0,1200'],
            'quota' => [$required, 'nullable', 'integer', 'min:0'],
            'tuition_fee' => [$required, 'nullable', 'string', 'max:255'],
            'description' => [$required, 'nullable', 'string'],
            'career_paths' => [$required, 'nullable', 'string'],
            'source_url' => [$required, 'nullable', 'string', 'max:2048'],
        ]);
    }
}
