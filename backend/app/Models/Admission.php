<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use HasFactory;

    // Tên bảng trong Database (thường Laravel tự hiểu là số nhiều 'admissions')
    protected $table = 'admissions';

    /**
     * fillable: Danh sách các cột được phép thêm/sửa dữ liệu từ API.
     * Phải khớp chính xác với các tên cột bạn đã tạo trong file Migration.
     */
    protected $fillable = [
        'name',     // Tên ngành
        'code',     // Mã ngành
        'group',    // Tổ hợp môn (A00, B00...)
        'quota',    // Chỉ tiêu tuyển sinh
        'tuition',  // Học phí
    ];

    /**
     * Nếu bạn muốn định nghĩa kiểu dữ liệu trả về cho API 
     * (ví dụ: quota luôn là số nguyên), có thể dùng casts.
     */
    protected $casts = [
        'quota' => 'integer',
    ];
}