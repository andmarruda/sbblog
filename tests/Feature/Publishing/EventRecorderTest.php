<?php

namespace Tests\Feature\Publishing;

use App\Models\Blog;
use App\Models\Category;
use App\Models\OutboxEvent;
use App\Publishing\EventRecorder;
use App\Publishing\Events\CategoryUpserted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class EventRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequences_are_gapless_and_per_blog(): void
    {
        $blog = Blog::factory()->create();
        $other = Blog::factory()->create();
        $recorder = app(EventRecorder::class);

        DB::transaction(function () use ($recorder, $blog, $other) {
            foreach (range(1, 3) as $i) {
                $recorder->record($blog, new CategoryUpserted(Category::factory()->for($blog)->create()));
            }

            $recorder->record($other, new CategoryUpserted(Category::factory()->for($other)->create()));
        });

        $this->assertSame([1, 2, 3], OutboxEvent::query()->whereBelongsTo($blog)->orderBy('id')->pluck('sequence')->all());
        $this->assertSame([1], OutboxEvent::query()->whereBelongsTo($other)->pluck('sequence')->all());
        $this->assertSame(3, $blog->event_sequence);
        $this->assertSame(3, $blog->fresh()?->event_sequence);
    }

    public function test_it_refuses_to_run_outside_a_transaction(): void
    {
        $blog = Blog::factory()->create();

        // RefreshDatabase wraps each test in a transaction; leave it to test the guard.
        DB::rollBack();

        try {
            $this->expectException(LogicException::class);
            app(EventRecorder::class)->record($blog, new CategoryUpserted(Category::factory()->for($blog)->make(['id' => fake()->uuid()])));
        } finally {
            DB::beginTransaction();
        }
    }

    public function test_a_rollback_discards_the_event_and_the_sequence(): void
    {
        $blog = Blog::factory()->create();
        $category = Category::factory()->for($blog)->create();

        try {
            DB::transaction(function () use ($blog, $category) {
                app(EventRecorder::class)->record($blog, new CategoryUpserted($category));

                throw new RuntimeException('write failed');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame(0, OutboxEvent::query()->count());
        $this->assertSame(0, $blog->fresh()?->event_sequence);
    }

    public function test_it_stores_type_version_and_payload(): void
    {
        $blog = Blog::factory()->create();
        $category = Category::factory()->for($blog)->create(['name' => 'News']);

        $event = DB::transaction(fn () => app(EventRecorder::class)->record($blog, new CategoryUpserted($category)));

        $this->assertSame('category.upserted', $event->type);
        $this->assertSame('category', $event->aggregate_type);
        $this->assertSame($category->id, $event->aggregate_id);
        $this->assertSame(1, $event->schema_version);
        $this->assertSame('News', $event->payload['category']['name']);
    }
}
