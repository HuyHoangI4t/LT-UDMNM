<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Tuyển sinh ĐH Tây Nguyên API',
    version: '1.0.0',
    description: 'Hệ thống Chatbot AI tư vấn tuyển sinh và phân tích xu hướng.'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Local development server'
)]
class OpenApi
{
    #[OA\Get(
        path: '/api/health',
        summary: 'Health check',
        tags: ['System'],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
        ]
    )]
    public function healthCheckDoc(): void
    {
    }
}