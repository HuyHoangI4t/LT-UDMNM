<?php

namespace App\Services;

use App\Models\AdmissionMajor;

class QuestionAnalyzerService
{
    public function analyze(string $question): array
    {
        $normalized = $this->normalize($question);

        return [
            'intent' => $this->detectIntent($normalized),
            'major' => $this->detectMajor($normalized),
            'year' => $this->detectYear($normalized),
            'category' => $this->detectCategory($normalized),
            'keyword' => $question,
            'normalized' => $normalized,
        ];
    }

    private function detectIntent(string $text): string
    {
        $rules = [
            'diem_chuan' => [
                'điểm chuẩn', 'diem chuan', 'bao nhiêu điểm', 'bao nhieu diem',
                'lấy mấy điểm', 'lay may diem', 'điểm xét', 'diem xet'
            ],
            'hoc_phi' => [
                'học phí', 'hoc phi', 'bao nhiêu tiền', 'bao nhieu tien',
                'tiền học', 'tien hoc'
            ],
            'to_hop' => [
                'tổ hợp', 'to hop', 'khối nào', 'khoi nao',
                'xét khối', 'xet khoi', 'a00', 'a01', 'b00', 'c00', 'd01'
            ],
            'chi_tieu' => [
                'chỉ tiêu', 'chi tieu', 'tuyển bao nhiêu', 'tuyen bao nhieu'
            ],
            'ma_nganh' => [
                'mã ngành', 'ma nganh'
            ],
            'co_hoi_viec_lam' => [
                'việc làm', 'viec lam', 'ra làm gì', 'ra lam gi',
                'cơ hội nghề', 'co hoi nghe'
            ],
            'hoc_bong' => [
                'học bổng', 'hoc bong'
            ],
            'ky_tuc_xa' => [
                'ký túc xá', 'ky tuc xa', 'ktx'
            ],
            'ho_so' => [
                'hồ sơ', 'ho so', 'thủ tục', 'thu tuc', 'đăng ký', 'dang ky'
            ],
            'tu_van_nganh' => [
                'nên học ngành nào', 'nen hoc nganh nao',
                'tư vấn ngành', 'tu van nganh',
                'phù hợp ngành nào', 'phu hop nganh nao'
            ],
        ];

        foreach ($rules as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $this->normalize($keyword))) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    private function detectMajor(string $text): ?string
    {
        $aliases = [
            'cntt' => 'Công nghệ thông tin',
            'it' => 'Công nghệ thông tin',
            'cong nghe thong tin' => 'Công nghệ thông tin',
            'công nghệ thông tin' => 'Công nghệ thông tin',

            'y khoa' => 'Y khoa',
            'bac si' => 'Y khoa',
            'bác sĩ' => 'Y khoa',

            'thu y' => 'Thú y',
            'thú y' => 'Thú y',

            'dieu duong' => 'Điều dưỡng',
            'điều dưỡng' => 'Điều dưỡng',

            'ke toan' => 'Kế toán',
            'kế toán' => 'Kế toán',

            'ngon ngu anh' => 'Ngôn ngữ Anh',
            'ngôn ngữ anh' => 'Ngôn ngữ Anh',

            'sp toan' => 'Sư phạm Toán học',
            'su pham toan' => 'Sư phạm Toán học',
            'sư phạm toán' => 'Sư phạm Toán học',
        ];

        foreach ($aliases as $alias => $majorName) {
            if (str_contains($text, $this->normalize($alias))) {
                return $majorName;
            }
        }

        $majors = AdmissionMajor::query()
            ->select('major_name')
            ->distinct()
            ->get();

        foreach ($majors as $major) {
            $majorName = $major->major_name;
            if (!$majorName) {
                continue;
            }

            if (str_contains($text, $this->normalize($majorName))) {
                return $majorName;
            }
        }

        return null;
    }

    private function detectYear(string $text): ?int
    {
        if (preg_match('/\b(20[0-9]{2})\b/u', $text, $matches)) {
            return (int) $matches[1];
        }

        if (str_contains($text, 'năm nay') || str_contains($text, 'nam nay')) {
            return (int) date('Y');
        }

        return null;
    }

    private function detectCategory(string $text): ?string
    {
        $categories = [
            'nganh_dao_tao',
            'dai_hoc',
            'sau_dai_hoc',
            'ngan_han',
            'vua_hoc_vua_lam',
            'faq',
        ];

        foreach ($categories as $category) {
            if (str_contains($text, $category)) {
                return $category;
            }
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return $text;
    }
}