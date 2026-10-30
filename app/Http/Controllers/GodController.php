<?php

namespace App\Http\Controllers;

use App\Models\God;
use App\Support\GodImage;
use App\Support\GodName;
use App\Support\GodType;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GodController extends Controller
{
    public function index()
    {
        $gods = God::orderBy('id')->get();
        return view('gods.index', compact('gods'));
    }

    // AJAX-подсказка: имя занято или свободно; окончательная проверка остаётся в validated().
    public function checkName(Request $request)
    {
        $raw = $request->query('name', '');
        if (!is_string($raw)) {
            return response()->json(['available' => false, 'message' => 'Укажите имя бога.']);
        }

        $name = GodName::normalize($raw);
        if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 60 ||
            !preg_match('/\A(?:[А-Яа-яЁё]+(?:[ -][А-Яа-яЁё]+)*|[A-Za-z]+(?:[ -][A-Za-z]+)*)\z/u', $name)) {
            return response()->json(['available' => false, 'message' => 'Введите 2–60 букв без цифр и специальных символов.']);
        }

        $query = God::query()->where('name_key', GodName::key($name));
        $excludeId = $request->query('exclude_id');
        if (is_string($excludeId) && ctype_digit($excludeId) && (int) $excludeId > 0) {
            $query->whereKeyNot((int) $excludeId);
        }
        $occupied = $query->exists();

        return response()->json([
            'available' => !$occupied,
            'message' => $occupied ? 'Бог с таким именем уже существует в пантеоне.' : 'Имя свободно.',
        ]);
    }

    public function create()
    {
        return view('gods.form', ['types' => GodType::OPTIONS]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $data['image_path'] = GodImage::save($request->file('image'));

        try {
            God::create($data);
        } catch (QueryException $e) {
            Storage::disk('public')->delete($data['image_path']);
            $this->rethrowNameConflict($e);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($data['image_path']);
            throw $e;
        }

        return redirect()->route('gods.index')->with('success', 'Бог добавлен.');
    }

    public function show(Request $request, God $god)
    {
        if ($request->ajax()) {
            return view('gods.partials.details', compact('god'));
        }
        return view('gods.show', compact('god'));
    }

    public function edit(God $god)
    {
        return view('gods.form', ['god' => $god, 'types' => GodType::OPTIONS]);
    }

    public function update(Request $request, God $god)
    {
        $data = $this->validated($request, false, $god);
        $oldImage = $god->image_path;
        $newImage = null;

        if ($request->hasFile('image')) {
            $newImage = GodImage::save($request->file('image'));
            $data['image_path'] = $newImage;
        }

        try {
            $god->update($data);
        } catch (QueryException $e) {
            if ($newImage) Storage::disk('public')->delete($newImage);
            $this->rethrowNameConflict($e);
        } catch (\Throwable $e) {
            if ($newImage) Storage::disk('public')->delete($newImage);
            throw $e;
        }

        if ($newImage) GodImage::removeUploaded($oldImage);
        return redirect()->route('gods.index')->with('success', 'Изменения сохранены.');
    }

    public function destroy(God $god)
    {
        $god->delete(); // Soft Deletes: проставляем deleted_at, не удаляя запись/фото.
        return redirect()->route('gods.index')->with('success', 'Бог удалён.');
    }

    // Единые правила для POST и PUT: сервер никогда не доверяет HTML-валидации.
    private function validated(Request $request, bool $creating, ?God $god = null): array
    {
        if (is_string($request->input('name'))) {
            $request->merge(['name' => GodName::normalize($request->input('name'))]);
        }
        foreach (['type', 'short_description', 'description', 'fact'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }

        $textHasLetters = function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && !preg_match('/\p{L}/u', $value)) {
                $fail('Поле «' . $this->attributes()[$attribute] . '» должно содержать осмысленный текст, а не только цифры или знаки.');
            }
        };

        $data = $request->validate([
            'name' => [
                'bail', 'required', 'string', 'min:2', 'max:60',
                'regex:/\A(?:[А-Яа-яЁё]+(?:[ -][А-Яа-яЁё]+)*|[A-Za-z]+(?:[ -][A-Za-z]+)*)\z/u',
                function (string $attribute, mixed $value, \Closure $fail) use ($god): void {
                    if (!is_string($value)) return;
                    $key = GodName::key($value);
                    $query = God::query()->where('name_key', $key);
                    if ($god) $query->whereKeyNot($god->id);
                    if ($query->exists()) {
                        $fail('Бог с таким именем уже существует в пантеоне.');
                    }
                },
            ],
            'type' => ['required', 'string', Rule::in(GodType::OPTIONS)],
            'published_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:today'],
            'short_description' => ['bail', 'required', 'string', 'min:20', 'max:300', $textHasLetters],
            'description' => ['bail', 'required', 'string', 'min:50', 'max:5000', $textHasLetters],
            'fact' => ['bail', 'required', 'string', 'min:12', 'max:500', $textHasLetters],
            'image' => [Rule::requiredIf($creating), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], $this->messages(), $this->attributes());

        // image — UploadedFile, а не поле модели; ключ настраивается только сервером.
        unset($data['image']);
        return $data;
    }

    private function rethrowNameConflict(QueryException $e): never
    {
        $message = mb_strtolower($e->getMessage(), 'UTF-8');
        if (str_contains($message, 'gods.name_key') || str_contains($message, 'gods_active_name_unique')) {
            throw ValidationException::withMessages([
                'name' => 'Бог с таким именем уже существует в пантеоне.',
            ]);
        }
        throw $e;
    }

    private function messages(): array
    {
        return [
            'required' => 'Заполните поле «:attribute».',
            'string' => 'Поле «:attribute» должно содержать текст.',
            'min.string' => 'Поле «:attribute»: минимум :min символов.',
            'max.string' => 'Поле «:attribute»: максимум :max символов.',
            'name.regex' => 'Имя: только русские или английские буквы, пробелы и дефисы (без цифр и знаков). Не смешивайте алфавиты.',
            'type.in' => 'Выберите сферу покровительства из списка.',
            'published_at.date_format' => 'Укажите корректную дату публикации.',
            'published_at.after_or_equal' => 'Дата публикации не может быть раньше 01.01.2000.',
            'published_at.before_or_equal' => 'Дата публикации не может быть в будущем.',
            'image.image' => 'Файл должен быть изображением.',
            'image.mimes' => 'Допустимые форматы изображения: JPG, PNG и WebP.',
            'image.max' => 'Изображение должно быть не больше 5 МБ.',
            'image.uploaded' => 'Не удалось загрузить файл. Проверьте размер и настройки PHP.',
        ];
    }

    private function attributes(): array
    {
        return [
            'name' => 'Имя бога',
            'type' => 'Сфера покровительства',
            'published_at' => 'Дата публикации',
            'short_description' => 'Краткое описание',
            'description' => 'История и покровительство',
            'fact' => 'Интересный факт',
            'image' => 'Изображение',
        ];
    }
}
