<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdmissionController extends Controller
{
    #[OA\Get(
        path: '/api/admissions',
        summary: 'Lấy danh sách ngành học',
        tags: ['Admission']
    )]
    #[OA\Response(
        response: 200,
        description: 'Thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Admission::orderBy('id', 'desc')->get()
        ]);
    }

    #[OA\Post(
        path: '/api/admissions',
        summary: 'Thêm mới một ngành học',
        tags: ['Admission']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'code', 'group', 'quota', 'tuition'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Kỹ thuật Phần mềm'),
                new OA\Property(property: 'code', type: 'string', example: '7480103'),
                new OA\Property(property: 'group', type: 'string', example: 'A00, A01'),
                new OA\Property(property: 'quota', type: 'integer', example: 100),
                new OA\Property(property: 'tuition', type: 'string', example: '15.000.000')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Tạo mới thành công')]
    public function store(Request $request)
    {
        $admission = Admission::create($request->all());
        return response()->json(['status' => 'success', 'data' => $admission], 201);
    }

    #[OA\Get(
        path: '/api/admissions/{id}',
        summary: 'Xem chi tiết một ngành học',
        tags: ['Admission']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Thành công')]
    #[OA\Response(response: 404, description: 'Không tìm thấy')]
    public function show($id)
    {
        $admission = Admission::find($id);
        if (!$admission) return response()->json(['message' => 'Không tìm thấy'], 404);
        return response()->json(['status' => 'success', 'data' => $admission]);
    }

    #[OA\Put(
        path: '/api/admissions/{id}',
        summary: 'Cập nhật thông tin ngành học',
        tags: ['Admission']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'quota', type: 'integer')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Cập nhật thành công')]
    public function update(Request $request, $id)
    {
        $admission = Admission::find($id);
        if (!$admission) return response()->json(['message' => 'Không tìm thấy'], 404);
        $admission->update($request->all());
        return response()->json(['status' => 'success', 'data' => $admission]);
    }

    #[OA\Delete(
        path: '/api/admissions/{id}',
        summary: 'Xóa một ngành học',
        tags: ['Admission']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Xóa thành công')]
    public function destroy($id)
    {
        Admission::destroy($id);
        return response()->json(['status' => 'success', 'message' => 'Đã xóa']);
    }
}