<?php

namespace Tests\Feature\Cms;

use App\Http\Middleware\CmsIpMiddleware;
use App\Models\KnowledgeSuggestion;
use App\Models\TrainingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingMaterialSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CmsIpMiddleware::class);
    }

    public function test_non_super_admin_cannot_upload_training_material(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        $this->actingAs($user);

        $response = $this->post(route('cms.training-materials.store'), [
            'category' => 'other',
            'content'  => 'Materi contoh',
        ]);

        $response->assertForbidden();
    }

    public function test_non_super_admin_cannot_delete_training_material(): void
    {
        $user     = User::factory()->create(['is_super_admin' => false]);
        $material = TrainingMaterial::create(['title' => 'X', 'category' => 'other', 'content' => 'isi', 'is_active' => true]);
        $this->actingAs($user);

        $response = $this->delete(route('cms.training-materials.destroy', $material));

        $response->assertForbidden();
    }

    public function test_non_super_admin_cannot_approve_knowledge_suggestion(): void
    {
        $user       = User::factory()->create(['is_super_admin' => false]);
        $suggestion = KnowledgeSuggestion::create([
            'question' => 'Q', 'answer' => 'A', 'type' => 'knowledge', 'status' => 'pending',
        ]);
        $this->actingAs($user);

        $response = $this->patch(route('cms.knowledge-suggestions.approve', $suggestion));

        $response->assertForbidden();
    }

    public function test_super_admin_can_upload_training_material(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($user);

        $response = $this->post(route('cms.training-materials.store'), [
            'category' => 'other',
            'content'  => 'Materi contoh',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('training_materials', ['content' => 'Materi contoh']);
    }
}
