@extends('layouts.app')
@section('title', $god->name)
@section('content')
    <main id="main" class="site-container main-content">
        <nav aria-label="Хлебные крошки" class="breadcrumbs"><a href="{{ route('gods.index') }}">Пантеон</a><span
                aria-hidden="true">/</span><span aria-current="page">{{ $god->name }}</span></nav>
        <div class="detail-toolbar"><a href="{{ route('gods.index') }}" class="back-link">← Назад к коллекции</a>
            <div class="d-flex gap-2"><a href="{{ route('gods.edit', $god) }}"
                    class="btn btn-primary">Редактировать</a><button type="button" class="btn btn-outline-danger"
                    data-bs-toggle="modal" data-bs-target="#deleteModal">Удалить</button></div>
        </div>
        <article class="detail-panel">@include('gods.partials.details')</article>
    </main>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-4" id="deleteTitle">Удалить из коллекции?</h2><button type="button"
                        class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">«{{ $god->name }}» больше не будет отображаться в пантеоне.</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-primary"
                        data-bs-dismiss="modal">Отмена</button>
                    <form action="{{ route('gods.destroy', $god) }}" method="POST">@csrf @method('DELETE')<button
                            type="submit" class="btn btn-danger">Удалить</button></form>
                </div>
            </div>
        </div>
    </div>
@endsection
