<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'TNU Admission Chatbot API',
    description: 'API tài liệu hóa cho chatbot tư vấn tuyển sinh, dashboard admin và CMS dữ liệu tuyển sinh.'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Local Laravel API'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    name: 'Authorization',
    in: 'header',
    description: 'Nhập theo định dạng: Bearer {token}'
)]
class OpenApi
{
}
