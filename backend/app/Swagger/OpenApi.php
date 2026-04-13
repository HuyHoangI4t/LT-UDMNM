<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
	title: 'Hệ thống Tư vấn Học vụ/Tuyển sinh với AI Chatbot API',
	version: '1.0.0',
	description: 'Backend Laravel cho hệ thống React, tích hợp gọi AI providers như Ollama và Gemini.'
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