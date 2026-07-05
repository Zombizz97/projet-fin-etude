<?php

namespace Tests\Unit\Models;

use App\Models\ForumCategory;
use App\Models\ForumTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $category = ForumCategory::create(['name' => 'General']);

        $this->assertEquals('General', $category->name);
    }

    public function test_has_no_timestamps(): void
    {
        $category = ForumCategory::factory()->create();

        $this->assertNull($category->updated_at);
    }

    public function test_has_many_topics(): void
    {
        $category = ForumCategory::factory()->create();
        $topic = ForumTopic::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($category->topics->contains($topic));
    }
}
