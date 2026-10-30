<?php

namespace Tests\Feature;

use App\Models\God;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GodCrudTest extends TestCase
{
    use RefreshDatabase;

    private function sample(): array
    {
        return [
            'name' => 'Тестовый бог',
            'type' => 'Бог неба и грома',
            'published_at' => now()->toDateString(),
            'short_description' => 'Краткое описание тестового персонажа для каталога.',
            'description' => 'Подробное описание вымышленного бога, достаточно длинное для проверки серверной валидации и редактора.',
            'fact' => 'Интересный и познавательный факт о божестве.',
        ];
    }

    private function postGod(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        Storage::fake('public');
        return $this->post(route('gods.store'), array_merge($this->sample(), [
            'image' => UploadedFile::fake()->image('god.jpg', 800, 600),
        ], $overrides));
    }

    public function test_list_show_and_modal_use_database(): void
    {
        $god = God::create($this->sample());
        $this->get('/')->assertRedirect('/gods');
        $this->get('/gods')->assertOk()->assertSee('Тестовый бог');
        $this->get(route('gods.show', $god))->assertOk()->assertSee($god->description);
        $this->get(route('gods.show', $god), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee($god->fact);
    }

    public function test_valid_create_saves_resized_image_and_publication_date(): void
    {
        if (!extension_loaded('gd')) $this->markTestSkipped('GD required');
        $this->postGod()->assertRedirect(route('gods.index'));
        $god = God::firstOrFail();
        $this->assertSame('Тестовый бог', $god->name);
        $this->assertSame('тестовый бог', $god->name_key);
        $this->assertSame(now()->toDateString(), $god->published_at->format('Y-m-d'));
        Storage::disk('public')->assertExists($god->image_path);
        $this->assertSame([1200, 800], array_slice(
            getimagesizefromstring(Storage::disk('public')->get($god->image_path)), 0, 2
        ));
    }

    public function test_live_name_availability_detects_duplicates_and_excludes_current_god(): void
    {
        $hades = God::create(array_merge($this->sample(), ['name' => 'Аид']));
        $this->getJson(route('gods.check-name', ['name' => 'аИД']))
            ->assertOk()->assertJsonPath('available', false);
        $this->getJson(route('gods.check-name', ['name' => '  АИД  ', 'exclude_id' => $hades->id]))
            ->assertOk()->assertJsonPath('available', true);
        $this->getJson(route('gods.check-name', ['name' => 'Гефест']))
            ->assertOk()->assertJsonPath('available', true);
        $this->getJson(route('gods.check-name', ['name' => 'Аид123']))
            ->assertOk()->assertJsonPath('available', false);
        $this->getJson(route('gods.check-name', ['name' => ['Аид']]))
            ->assertOk()->assertJsonPath('available', false);
    }

    public function test_create_and_edit_forms_have_progressive_validation_hooks(): void
    {
        $god = God::create($this->sample());
        $this->get(route('gods.create'))->assertOk()
            ->assertSee('data-check-name-url', false)
            ->assertSee('nameTypingNotice')
            ->assertSee('live-validation.js');
        $this->get(route('gods.edit', $god))->assertOk()
            ->assertSee('data-editing-id', false)
            ->assertSee('live-validation.js');
    }

    public function test_duplicate_name_rejected_even_with_different_case_and_spaces(): void
    {
        if (!extension_loaded('gd')) $this->markTestSkipped('GD required');
        God::create(array_merge($this->sample(), ['name' => 'Аид']));
        foreach (['Аид', 'аид', '  АИД  ', "Аид\u{00A0}", "АиД"] as $name) {
            $this->postGod(['name' => $name])->assertSessionHasErrors('name');
        }
        $this->assertDatabaseCount('gods', 1);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_duplicate_name_rejected_for_different_yo_spelling(): void
    {
        if (!extension_loaded('gd')) $this->markTestSkipped('GD required');
        God::create(array_merge($this->sample(), ['name' => 'Селёна']));
        $this->postGod(['name' => 'селена'])->assertSessionHasErrors('name');
        $this->assertDatabaseCount('gods', 1);
    }

    public function test_update_cannot_rename_to_existing_god_but_can_keep_own_name(): void
    {
        $hades = God::create(array_merge($this->sample(), ['name' => 'Аид']));
        $zeus = God::create(array_merge($this->sample(), ['name' => 'Зевс']));
        $this->put(route('gods.update', $zeus), array_merge($this->sample(), [
            'name' => 'аИД',
        ]))->assertSessionHasErrors('name');
        $this->assertSame('Зевс', $zeus->fresh()->name);
        $this->put(route('gods.update', $hades), array_merge($this->sample(), [
            'name' => '  АИД  ',
        ]))->assertRedirect(route('gods.index'));
        $this->assertSame('АИД', $hades->fresh()->name);
        $this->assertSame('аид', $hades->fresh()->name_key);
    }

    public function test_invalid_names_are_rejected(): void
    {
        if (!extension_loaded('gd')) $this->markTestSkipped('GD required');
        foreach (['Аид123', '123', 'Аид!', 'Аид_', 'Aид', '-Зевс', 'Зевс--бог', 'З', ''] as $name) {
            $this->postGod(['name' => $name])->assertSessionHasErrors('name');
        }
        $this->assertDatabaseCount('gods', 0);
    }

    public function test_invalid_type_text_and_dates_rejected_on_create_and_update(): void
    {
        if (!extension_loaded('gd')) $this->markTestSkipped('GD required');
        $bad = [
            'type' => 'Сам придумал сферу',
            'published_at' => now()->addDay()->toDateString(),
            'short_description' => str_repeat('1', 45),
            'description' => str_repeat('@', 60),
            'fact' => str_repeat('7', 30),
        ];
        $this->postGod($bad)->assertSessionHasErrors(array_keys($bad));
        $this->assertDatabaseCount('gods', 0);
        $god = God::create($this->sample());
        $this->put(route('gods.update', $god), array_merge($this->sample(), $bad))
            ->assertSessionHasErrors(array_keys($bad));
        $this->assertSame('Бог неба и грома', $god->fresh()->type);
    }

    public function test_bad_date_and_text_length_are_rejected(): void
    {
        if (!extension_loaded('gd')) $this->markTestSkipped('GD required');
        $this->postGod([
            'published_at' => '2026-02-30',
            'short_description' => 'Коротко',
            'description' => 'Недостаточно текста',
            'fact' => 'факт',
        ])->assertSessionHasErrors(['published_at','short_description','description','fact']);
    }

    public function test_required_fields_and_image_upload_are_checked(): void
    {
        $this->post(route('gods.store'), [])->assertSessionHasErrors([
            'name', 'type', 'published_at', 'short_description', 'description', 'fact', 'image',
        ]);
        Storage::fake('public');
        $this->post(route('gods.store'), array_merge($this->sample(), [
            'image' => UploadedFile::fake()->create('fake.txt', 1, 'text/plain'),
        ]))->assertSessionHasErrors('image');
        $this->assertDatabaseCount('gods', 0);
    }

    public function test_invalid_update_preserves_existing_data_and_image(): void
    {
        $god = God::create(array_merge($this->sample(), ['image_path' => 'gods/Zeus.jpg']));
        $this->put(route('gods.update', $god), array_merge($this->sample(), [
            'name' => 'Зевс123', 'description' => '1234567890',
        ]))->assertSessionHasErrors(['name', 'description']);
        $this->assertSame('Тестовый бог', $god->fresh()->name);
        $this->assertSame('gods/Zeus.jpg', $god->fresh()->image_path);
    }

    public function test_replacing_image_deletes_previous_uploaded_file(): void
    {
        if (!extension_loaded('gd')) $this->markTestSkipped('GD required');
        $this->postGod()->assertRedirect(route('gods.index'));
        $god = God::firstOrFail();
        $old = $god->image_path;
        $this->put(route('gods.update', $god), array_merge($this->sample(), [
            'image' => UploadedFile::fake()->image('updated.png', 700, 1200),
        ]))->assertRedirect(route('gods.index'));
        $this->assertNotSame($old, $god->fresh()->image_path);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($god->fresh()->image_path);
    }

    public function test_soft_deletion_allows_reusing_name_but_keeps_old_record(): void
    {
        $god = God::create($this->sample());
        $this->delete(route('gods.destroy', $god))->assertRedirect(route('gods.index'));
        $this->assertSoftDeleted('gods', ['id' => $god->id]);
        $this->get(route('gods.show', $god))->assertNotFound();
        $new = God::create(array_merge($this->sample(), ['name' => ' ТЕСТОВЫЙ БОГ ']));
        $this->assertNotEquals($god->id, $new->id);
        $this->assertSame('тестовый бог', $new->name_key);
    }

    public function test_database_unique_index_protects_against_direct_duplicate_create(): void
    {
        God::create(array_merge($this->sample(), ['name' => 'Аид']));
        $this->expectException(QueryException::class);
        God::create(array_merge($this->sample(), ['name' => '  аид  ']));
    }

    public function test_form_has_select_date_and_both_actions(): void
    {
        $god = God::create($this->sample());
        $this->get(route('gods.create'))->assertOk()
            ->assertSee('name="type"', false)
            ->assertSee('type="date"', false)
            ->assertSee('Добавить в пантеон');
        $this->get(route('gods.edit', $god))->assertOk()
            ->assertSee('Сохранить изменения');
        $this->get(route('gods.index'))->assertOk()
            ->assertSee('godModalDeleteForm')
            ->assertSee('godModalEdit')
            ->assertDontSee('Открыть страницу');
    }

    public function test_html_output_is_escaped(): void
    {
        $god = God::create(array_merge($this->sample(), ['fact' => '<script>alert(1)</script>']));
        $this->get(route('gods.show', $god))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_mutators_normalize_name_and_date_fields(): void
    {
        $god = God::create(array_merge($this->sample(), ['name' => "  Новый   бог  "]));
        $god->created_at = '2026-10-15 12:34:56';
        $god->updated_at = '2026-10-16 10:20:30';
        $god->save();
        $god->refresh();
        $this->assertSame('Новый бог', $god->name);
        $this->assertSame('новый бог', $god->name_key);
        $this->assertInstanceOf(\Carbon\Carbon::class, $god->created_at);
        $this->assertSame('2026-10-16 10:20:30', $god->updated_at->toDateTimeString());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('gods', 5);
    }
}
