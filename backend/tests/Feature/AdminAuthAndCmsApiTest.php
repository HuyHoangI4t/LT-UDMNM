<?php

namespace Tests\Feature;

use App\Models\AdmissionMajor;
use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthAndCmsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_logout_with_sanctum_token(): void
    {
        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'password123',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'admin@example.test',
            'password' => 'password123',
        ]);

        $login
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $token = $login->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_knowledge_base_crud_endpoint_updates_content(): void
    {
        $knowledge = KnowledgeBase::create([
            'category' => 'hoc_phi',
            'source_type' => 'hoc_phi',
            'title' => 'Old title',
            'content' => 'Old content',
        ]);

        $this->putJson('/api/knowledge-bases/' . $knowledge->id, [
            'title' => 'Updated title',
            'content' => 'Updated content',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.content', 'Updated content');
    }

    public function test_admission_major_crud_endpoint_creates_major(): void
    {
        $this->postJson('/api/admission-majors', [
            'year' => 2026,
            'major_name' => 'Cong nghe thong tin',
            'major_code' => '7480201',
            'subject_groups' => ['A00', 'A01'],
            'quota' => 120,
        ])
            ->assertCreated()
            ->assertJsonPath('data.major_code', '7480201');

        $this->assertDatabaseHas('admission_majors', [
            'major_name' => 'Cong nghe thong tin',
            'major_code' => '7480201',
        ]);
    }
}
