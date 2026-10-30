@extends('layouts.app')
@php($editing = isset($god))
@section('title', $editing ? 'Редактировать бога' : 'Добавить бога')
@section('content')
    <main id="main" class="site-container main-content">
        <nav aria-label="Хлебные крошки" class="breadcrumbs">
            <a href="{{ route('gods.index') }}">Пантеон</a><span aria-hidden="true">/</span>
            <span aria-current="page">{{ $editing ? 'Редактирование' : 'Новая история' }}</span>
        </nav>
        <div class="form-heading">
            <p class="eyebrow">{{ $editing ? 'ОБНОВЛЕНИЕ КОЛЛЕКЦИИ' : 'НОВАЯ ИСТОРИЯ' }}</p>
            <h1>{{ $editing ? 'Редактировать бога' : 'Добавить бога' }}</h1>
            <p>{{ $editing ? 'Уточните детали и сохраните изменения.' : 'Расскажите о герое мифов и его месте в древнегреческом мире.' }}</p>
        </div>

        <form action="{{ $editing ? route('gods.update', $god) : route('gods.store') }}" method="POST"
              enctype="multipart/form-data" class="needs-validation god-form" novalidate
              data-check-name-url="{{ route('gods.check-name') }}"
              data-editing-id="{{ $editing ? $god->id : '' }}">
            @csrf
            @if ($editing) @method('PUT') @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Исправьте ошибки формы:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                    <p class="mb-0 mt-2">После ошибки выберите изображение повторно, если загружали его.</p>
                </div>
            @endif

            <div class="row g-4 align-items-start">
                <div class="col-lg-8">
                    <section class="form-panel">
                        <div class="panel-heading"><span>01</span><h2>О божестве</h2></div>
                        <p class="form-note">Все поля обязательны. Для существующих имён дубли запрещены.</p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Имя бога</label>
                                <input id="name" name="name" type="text" autocomplete="off"
                                       value="{{ old('name', $editing ? $god->name : '') }}"
                                       placeholder="Например, Гермес" minlength="2" maxlength="60"
                                       pattern="(?:[А-Яа-яЁё]+(?:[ -][А-Яа-яЁё]+)*|[A-Za-z]+(?:[ -][A-Za-z]+)*)"
                                       title="Только русские или английские буквы, пробелы и дефисы. Без цифр и знаков."
                                       required class="form-control @error('name') is-invalid @enderror"
                                       aria-describedby="nameHelp nameError nameLiveStatus nameTypingNotice" inputmode="text">
                                <div id="nameError" class="invalid-feedback">
                                    @error('name') {{ $message }} @else Допустимы только буквы, пробелы и дефисы (2–60 символов). @enderror
                                </div>
                                <div id="nameHelp" class="form-text">Имя проверяется при вводе и при переходе к другому полю. Цифры и спецсимволы не вводятся.</div>
                                <div id="nameTypingNotice" class="name-input-warning" role="status" aria-live="polite" hidden></div>
                                <div id="nameLiveStatus" class="field-live-status" role="status" aria-live="polite" hidden></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="type">Сфера покровительства</label>
                                <select id="type" name="type" required
                                        class="form-select @error('type') is-invalid @enderror"
                                        aria-describedby="typeError">
                                    <option value="">— Выберите сферу —</option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type }}" @selected(old('type', $editing ? $god->type : '') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                                <div id="typeError" class="invalid-feedback">
                                    @error('type') {{ $message }} @else Выберите значение из списка. @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="published_at">Дата публикации карточки</label>
                                <input id="published_at" name="published_at" type="date" required
                                       min="2000-01-01" max="{{ now()->toDateString() }}"
                                       value="{{ old('published_at', $editing ? $god->published_at?->format('Y-m-d') : now()->toDateString()) }}"
                                       class="form-control @error('published_at') is-invalid @enderror"
                                       aria-describedby="publishedAtHelp publishedAtError">
                                <div id="publishedAtError" class="invalid-feedback">
                                    @error('published_at') {{ $message }} @else Укажите дату от 01.01.2000 до сегодняшнего дня. @enderror
                                </div>
                                <div id="publishedAtHelp" class="form-text">Это дата публикации на сайте, не мифологическая дата рождения.</div>
                            </div>
                        </div>

                        @foreach ([
                            'short_description' => ['Краткое описание', 20, 300, 3, 'Несколько предложений для карточки в каталоге.'],
                            'description' => ['История и покровительство', 50, 5000, 6, 'Мифы, символы и роль божества в древнегреческом мире.'],
                            'fact' => ['Интересный факт', 12, 500, 3, 'Одна запоминающаяся деталь о божестве.'],
                        ] as $field => $settings)
                            <div class="mb-4">
                                <div class="field-label-row">
                                    <label class="form-label" for="{{ $field }}">{{ $settings[0] }}</label>
                                    <span class="field-counter" data-counter-for="{{ $field }}"></span>
                                </div>
                                <textarea id="{{ $field }}" name="{{ $field }}" rows="{{ $settings[3] }}"
                                          required minlength="{{ $settings[1] }}" maxlength="{{ $settings[2] }}"
                                          data-requires-letters="true"
                                          class="form-control @error($field) is-invalid @enderror"
                                          aria-describedby="{{ $field }}Help {{ $field }}Error">{{ old($field, $editing ? $god->$field : '') }}</textarea>
                                <div id="{{ $field }}Error" class="invalid-feedback">
                                    @error($field) {{ $message }} @else Минимум {{ $settings[1] }} символов, обязательно должны быть буквы. @enderror
                                </div>
                                <div id="{{ $field }}Help" class="form-text">{{ $settings[4] }} Не заполняйте поле только цифрами или знаками.</div>
                            </div>
                        @endforeach
                    </section>
                </div>
                <div class="col-lg-4">
                    <section class="form-panel image-panel">
                        <div class="panel-heading"><span>02</span><h2>Изображение</h2></div>
                        <div class="upload-preview">
                            <img id="imagePreview"
                                 src="{{ $editing && $god->image_path ? asset('storage/' . $god->image_path) : '' }}"
                                 alt="Предпросмотр изображения" @if (!$editing || !$god->image_path) hidden @endif>
                            <div id="imagePlaceholder" @if ($editing && $god->image_path) hidden @endif>
                                <span aria-hidden="true">Ω</span><p>Лицо вашей истории</p>
                            </div>
                        </div>
                        <label class="form-label" for="image">{{ $editing ? 'Заменить изображение' : 'Загрузить изображение' }}</label>
                        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp"
                               class="form-control @error('image') is-invalid @enderror" @if (!$editing) required @endif
                               aria-describedby="imageHelp imageError">
                        <div class="invalid-feedback" id="imageError">
                            @error('image') {{ $message }} @else Выберите JPG, PNG или WebP размером до 5 МБ. @enderror
                        </div>
                        <p class="form-text" id="imageHelp">JPG, PNG или WebP · до 5 МБ · до 12 Мп. Изображение обрезается по центру до формата 3:2.</p>
                        @if ($editing) <p class="form-note mb-0">Оставьте поле пустым, чтобы сохранить фотографию.</p> @endif
                    </section>
                </div>
            </div>
            <div class="form-actions">
                <span>Поля проверяются после выхода из них и при каждом изменении. Сервер также проверяет данные.</span>
                <div class="d-flex gap-2">
                    <a href="{{ route('gods.index') }}" class="btn btn-outline-primary">Отмена</a>
                    <button type="submit" class="btn btn-primary">{{ $editing ? 'Сохранить изменения' : 'Добавить в пантеон' }}</button>
                </div>
            </div>
        </form>
    </main>
@endsection
