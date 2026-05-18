<?php

namespace App\Services;

class PdfExtractService
{
    public function extract(string $filePath): string
    {
        return is_file($filePath) ? '' : '';
    }
}
