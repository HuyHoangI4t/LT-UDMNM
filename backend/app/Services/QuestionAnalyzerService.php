<?php

namespace App\Services;

use App\Models\AdmissionMajor;
use Illuminate\Support\Str;

class QuestionAnalyzerService
{
    public function analyze(string $question): array
    {
        $normalized = $this->normalize($question);
        $ascii = $this->normalizeAscii($question);

        $intent = $this->detectIntent($normalized, $ascii);
        $major = $this->detectMajor($normalized, $ascii);
        $year = $this->detectYear($normalized, $ascii);
        $admissionMethod = $this->detectAdmissionMethod($normalized, $ascii);
        $score = $this->detectScore($normalized);
        $province = $this->detectProvince($normalized, $ascii);
        $profileSignals = $this->detectProfileSignals($normalized, $ascii);

        return [
            'intent' => $intent,
            'major' => $major,
            'year' => $year,
            'category' => $this->detectCategory($normalized, $ascii, $intent),
            'admission_method' => $admissionMethod,
            'score' => $score,
            'province' => $province,
            'keyword' => $question,
            'normalized' => $normalized,
            'entities' => [
                'major_name' => $major,
                'year' => $year,
                'admission_method' => $admissionMethod,
                'score' => $score,
                'province' => $province,
                'strengths' => $profileSignals['strengths'],
                'interests' => $profileSignals['interests'],
                'career_orientation' => $profileSignals['career_orientation'],
            ],
        ];
    }

    private function detectIntent(string $text, string $ascii): string
    {
        $rules = [
            'diem_chuan' => ['điểm chuẩn', 'điểm xét', 'bao nhiêu điểm', 'lấy mấy điểm', 'diem chuan', 'diem xet', 'bao nhieu diem', 'lay may diem'],
            'hoc_phi' => ['học phí', 'tiền học', 'bao nhiêu tiền', 'hoc phi', 'tien hoc', 'bao nhieu tien'],
            'to_hop' => ['tổ hợp', 'khối nào', 'xét khối', 'a00', 'a01', 'b00', 'c00', 'd01', 'to hop', 'khoi nao', 'xet khoi'],
            'chi_tieu' => ['chỉ tiêu', 'tuyển bao nhiêu', 'chi tieu', 'tuyen bao nhieu'],
            'ma_nganh' => ['mã ngành', 'ma nganh'],
            'co_hoi_viec_lam' => ['việc làm', 'ra làm gì', 'cơ hội nghề', 'nghề nghiệp', 'viec lam', 'ra lam gi', 'co hoi nghe', 'nghe nghiep'],
            'chuong_trinh_dao_tao' => ['chương trình đào tạo', 'học những môn', 'khung chương trình', 'chuong trinh dao tao', 'hoc nhung mon', 'khung chuong trinh'],
            'hoc_bong' => ['học bổng', 'hoc bong'],
            'ky_tuc_xa' => ['ký túc xá', 'ktx', 'ky tuc xa'],
            'ho_so' => ['hồ sơ', 'thủ tục', 'đăng ký', 'phương thức xét tuyển', 'ho so', 'thu tuc', 'dang ky', 'phuong thuc xet tuyen'],
            'tu_van_nganh' => ['nên học ngành nào', 'tư vấn ngành', 'phù hợp ngành nào', 'sở thích', 'năng lực', 'định hướng', 'nen hoc nganh nao', 'tu van nganh', 'phu hop nganh nao', 'so thich', 'nang luc', 'dinh huong'],
            'nganh_dao_tao' => ['ngành đào tạo', 'có ngành nào', 'danh sách ngành', 'nganh dao tao', 'co nganh nao', 'danh sach nganh'],
        ];

        foreach ($rules as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                $needle = str_contains($keyword, 'đ') || preg_match('/[^\x00-\x7F]/', $keyword)
                    ? $this->normalize($keyword)
                    : $this->normalizeAscii($keyword);

                if (str_contains($text, $needle) || str_contains($ascii, $needle)) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    private function detectMajor(string $text, string $ascii): ?string
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
            if (str_contains($text, $this->normalize($alias)) || str_contains($ascii, $this->normalizeAscii($alias))) {
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

            if (
                str_contains($text, $this->normalize($majorName)) ||
                str_contains($ascii, $this->normalizeAscii($majorName))
            ) {
                return $majorName;
            }
        }

        return null;
    }

    private function detectYear(string $text, string $ascii): ?int
    {
        if (preg_match('/\b(20[0-9]{2})\b/u', $text, $matches)) {
            return (int) $matches[1];
        }

        if (str_contains($text, 'năm nay') || str_contains($ascii, 'nam nay')) {
            return (int) date('Y');
        }

        return null;
    }

    private function detectAdmissionMethod(string $text, string $ascii): ?string
    {
        $rules = [
            'thpt' => ['thpt', 'tốt nghiệp', 'tot nghiep'],
            'hoc_ba' => ['học bạ', 'hoc ba'],
            'dgnl' => ['đánh giá năng lực', 'dgnl', 'danh gia nang luc'],
            'tuyen_thang' => ['tuyển thẳng', 'tuyen thang'],
            'ket_hop' => ['kết hợp', 'ket hop'],
        ];

        foreach ($rules as $method => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $this->normalize($keyword)) || str_contains($ascii, $this->normalizeAscii($keyword))) {
                    return $method;
                }
            }
        }

        return null;
    }

    private function detectScore(string $text): ?float
    {
        if (preg_match('/(?:được|duoc|em\s+duoc|đạt|dat)?\s*(\d{1,2}(?:[,.]\d{1,2})?)\s*(?:điểm|diem)\b/u', $text, $matches)) {
            return (float) str_replace(',', '.', $matches[1]);
        }

        return null;
    }

    private function detectProvince(string $text, string $ascii): ?string
    {
        $provinces = [
            'Đắk Lắk', 'Đắk Nông', 'Gia Lai', 'Kon Tum', 'Lâm Đồng', 'Khánh Hòa',
            'Phú Yên', 'Bình Định', 'Quảng Nam', 'Quảng Ngãi', 'Hồ Chí Minh',
            'Hà Nội', 'Đà Nẵng', 'Cần Thơ',
        ];

        foreach ($provinces as $province) {
            if (str_contains($text, $this->normalize($province)) || str_contains($ascii, $this->normalizeAscii($province))) {
                return $province;
            }
        }

        return null;
    }

    private function detectProfileSignals(string $text, string $ascii): array
    {
        return [
            'strengths' => $this->collectMatches($text, $ascii, [
                'toán' => ['toán', 'logic', 'tính toán', 'toan', 'tinh toan'],
                'ngoại ngữ' => ['tiếng anh', 'ngoại ngữ', 'tieng anh', 'ngoai ngu'],
                'giao tiếp' => ['giao tiếp', 'thuyết trình', 'giao tiep', 'thuyet trinh'],
                'sinh học' => ['sinh học', 'y sinh', 'sinh hoc'],
            ]),
            'interests' => $this->collectMatches($text, $ascii, [
                'công nghệ' => ['công nghệ', 'máy tính', 'lập trình', 'cong nghe', 'may tinh', 'lap trinh'],
                'kinh doanh' => ['kinh doanh', 'kế toán', 'tài chính', 'ke toan', 'tai chinh'],
                'y tế' => ['y tế', 'chăm sóc sức khỏe', 'y te', 'cham soc suc khoe'],
                'sư phạm' => ['dạy học', 'giáo viên', 'sư phạm', 'day hoc', 'giao vien', 'su pham'],
            ]),
            'career_orientation' => $this->collectMatches($text, $ascii, [
                'ổn định' => ['ổn định', 'on dinh'],
                'thu nhập cao' => ['thu nhập cao', 'lương cao', 'thu nhap cao', 'luong cao'],
                'làm việc với con người' => ['làm việc với con người', 'tu van', 'chăm sóc', 'cham soc'],
                'nghiên cứu' => ['nghiên cứu', 'nghien cuu'],
            ]),
        ];
    }

    private function collectMatches(string $text, string $ascii, array $rules): array
    {
        $matches = [];

        foreach ($rules as $label => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $this->normalize($keyword)) || str_contains($ascii, $this->normalizeAscii($keyword))) {
                    $matches[] = $label;
                    break;
                }
            }
        }

        return array_values(array_unique($matches));
    }

    private function detectCategory(string $text, string $ascii, string $intent): ?string
    {
        $intentMap = [
            'diem_chuan' => 'diem_chuan',
            'hoc_phi' => 'hoc_phi',
            'hoc_bong' => 'hoc_bong',
            'ky_tuc_xa' => 'ky_tuc_xa',
            'co_hoi_viec_lam' => 'viec_lam',
            'chuong_trinh_dao_tao' => 'chuong_trinh_dao_tao',
            'nganh_dao_tao' => 'nganh_dao_tao',
        ];

        if (isset($intentMap[$intent])) {
            return $intentMap[$intent];
        }

        $categories = ['nganh_dao_tao', 'dai_hoc', 'sau_dai_hoc', 'ngan_han', 'vua_hoc_vua_lam', 'faq'];

        foreach ($categories as $category) {
            if (str_contains($text, $category) || str_contains($ascii, $category)) {
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

    private function normalizeAscii(string $text): string
    {
        $text = Str::ascii($this->normalize($text));
        $text = preg_replace('/[^a-z0-9\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
