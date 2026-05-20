<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class KnowledgeBaseController extends Controller
{
    #[OA\Get(
        path: '/api/knowledge-bases',
        summary: 'Danh sach knowledge base',
        security: [['sanctum' => []]],
        tags: ['Knowledge Base'],
        responses: [new OA\Response(response: 200, description: 'Danh sach knowledge base')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $search = trim((string) $request->query('q', ''));

        $query = KnowledgeBase::query()->latest();

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $like = "%{$search}%";
                $sub->where('title', 'like', $like)
                    ->orWhere('content', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('source_type', 'like', $like);
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate($perPage),
        ]);
    }

    #[OA\Get(
        path: '/api/knowledge-bases/{knowledge_base}',
        summary: 'Chi tiet knowledge base',
        security: [['sanctum' => []]],
        tags: ['Knowledge Base'],
        parameters: [new OA\Parameter(name: 'knowledge_base', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Chi tiet knowledge base')]
    )]
    public function show(KnowledgeBase $knowledgeBase): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $knowledgeBase,
        ]);
    }

    #[OA\Post(
        path: '/api/knowledge-bases',
        summary: 'Tao knowledge base',
        security: [['sanctum' => []]],
        tags: ['Knowledge Base'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'category', type: 'string', example: 'nganh_dao_tao'),
                    new OA\Property(property: 'source_type', type: 'string', example: 'nganh_dao_tao'),
                    new OA\Property(property: 'title', type: 'string', example: 'Cong nghe thong tin 2025'),
                    new OA\Property(property: 'content', type: 'string', example: 'Noi dung tuyen sinh...'),
                    new OA\Property(property: 'url', type: 'string', example: 'https://example.com'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Tao thanh cong')]
    )]
    public function store(Request $request): JsonResponse
    {
        $knowledgeBase = KnowledgeBase::create($this->validatedData($request));

        return response()->json([
            'status' => 'success',
            'data' => $knowledgeBase,
        ], 201);
    }

    #[OA\Put(
        path: '/api/knowledge-bases/{knowledge_base}',
        summary: 'Cap nhat knowledge base',
        security: [['sanctum' => []]],
        tags: ['Knowledge Base'],
        parameters: [new OA\Parameter(name: 'knowledge_base', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Tieu de moi'),
            new OA\Property(property: 'content', type: 'string', example: 'Noi dung moi'),
        ])),
        responses: [new OA\Response(response: 200, description: 'Cap nhat thanh cong')]
    )]
    public function update(Request $request, KnowledgeBase $knowledgeBase): JsonResponse
    {
        $knowledgeBase->update($this->validatedData($request, true));

        return response()->json([
            'status' => 'success',
            'data' => $knowledgeBase->refresh(),
        ]);
    }

    #[OA\Delete(
        path: '/api/knowledge-bases/{knowledge_base}',
        summary: 'Xoa knowledge base',
        security: [['sanctum' => []]],
        tags: ['Knowledge Base'],
        parameters: [new OA\Parameter(name: 'knowledge_base', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Xoa thanh cong')]
    )]
    public function destroy(KnowledgeBase $knowledgeBase): JsonResponse
    {
        $knowledgeBase->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Knowledge base deleted.',
        ]);
    }

    private function validatedData(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'nullable';

        return $request->validate([
            'category' => [$required, 'string', 'max:255'],
            'source_type' => [$required, 'string', 'max:255'],
            'title' => [$required, 'string', 'max:255'],
            'url' => [$required, 'nullable', 'string'],
            'content' => [$required, 'nullable', 'string'],
            'pdf_links' => [$required, 'nullable', 'array'],
            'image_links' => [$required, 'nullable', 'array'],
            'published_at' => [$required, 'nullable', 'date'],
            'embedding' => [$required, 'nullable', 'string'],
        ]);
    }
}
