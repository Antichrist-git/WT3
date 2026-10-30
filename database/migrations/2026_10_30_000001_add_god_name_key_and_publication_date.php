<?php

use App\Support\GodName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gods', function (Blueprint $table) {
            $table->string('name_key', 160)->nullable();
            $table->date('published_at')->nullable();
        });

        // Не удаляем старые записи и не трогаем их изображения.
        // Для существующих карточек дата публикации = день их создания.
        $used = [];
        foreach (DB::table('gods')->orderBy('id')->get() as $god) {
            $key = GodName::key($god->name);
            // Совпадение среди активных записей нельзя исправлять молча.
            if ($god->deleted_at === null && isset($used[$key])) {
                throw new RuntimeException(
                    "Дубли в gods (ID {$used[$key]} и {$god->id}): '{$god->name}'. " .
                    'Измените повторяющееся имя, затем выполните php artisan migrate повторно.'
                );
            }
            if ($god->deleted_at === null) {
                $used[$key] = $god->id;
            }
            DB::table('gods')->where('id', $god->id)->update([
                'name_key' => $key,
                'published_at' => $god->created_at
                    ? substr((string) $god->created_at, 0, 10)
                    : now()->toDateString(),
            ]);
        }

        // SQLite: один активный бог с данным нормализованным именем.
        // Мягко удалённые записи остаются в БД, но не блокируют новые имена.
        DB::statement('CREATE UNIQUE INDEX gods_active_name_unique ON gods (name_key) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS gods_active_name_unique');
        Schema::table('gods', function (Blueprint $table) {
            $table->dropColumn(['name_key', 'published_at']);
        });
    }
};
