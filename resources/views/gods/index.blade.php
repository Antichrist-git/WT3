@extends('layouts.app')
@section('title', 'Боги Древней Греции')
@section('content')
    <main id="main" class="site-container main-content">
        <section class="hero" aria-labelledby="page-title">
            <div class="hero-copy">
                <p class="eyebrow">АНТИЧНОСТЬ БЛИЖЕ, ЧЕМ КАЖЕТСЯ</p>
                <h1 id="page-title">Боги<br>Древней Греции<span>.</span></h1>
                <p class="hero-description">Властители стихий, хранители мудрости и герои вечных историй. Откройте для себя
                    древнегреческий пантеон.</p>
                <a class="hero-link" href="#collection">Исследовать пантеон <span aria-hidden="true">↗</span></a>
            </div>
            <div class="hero-art" role="img" aria-label="Горный пейзаж Олимпа"
                style="background-image: url('{{ asset('storage/gods/olympus.jpg') }}')">
                <span class="hero-art-top">ΕΛΛΗΝΙΚΗ ΜΥΘΟΛΟΓΙΑ</span>
                <div class="hero-caption"><span>01 / ИСТОКИ ЛЕГЕНД</span><strong>Там, где рождаются мифы</strong></div>
            </div>
        </section>
        <section id="collection" class="collection" aria-labelledby="collection-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">КОЛЛЕКЦИЯ</p>
                    <h2 id="collection-title">Древнегреческий пантеон <span class="count-badge">{{ $gods->count() }}</span>
                    </h2>
                </div><span class="section-note">У каждого бога — своя история</span>
            </div>
            @if ($gods->isNotEmpty())
                <div class="collection-toolbar">
                    <label class="search-box"><span aria-hidden="true">⌕</span><input type="search" id="godSearch"
                            class="form-control" placeholder="Найти бога или сферу покровительства…"
                            aria-label="Поиск по имени и сфере покровительства"></label>
                    <div class="filter-box"><label for="godType" class="visually-hidden">Сфера
                            покровительства</label><select id="godType" class="form-select">
                            <option value="">Все сферы</option>
                            @foreach ($gods->pluck('type')->unique()->sort() as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select></div>
                    <span id="resultCount" class="result-count" role="status">Показано: {{ $gods->count() }}</span>
                </div>
            @endif
            <div class="row g-4" id="godsGrid">
                @forelse ($gods as $god)
                    <div class="god-column col-12 col-md-6 col-lg-4" data-search="{{ $god->name }} {{ $god->type }}"
                        data-type="{{ $god->type }}">
                        <article class="card god-card h-100">
                            <a class="card-art js-god-detail" href="{{ route('gods.show', $god) }}"
                                aria-label="{{ $god->name }} — подробности">
                                @if ($god->image_path)
                                    <img src="{{ asset('storage/' . $god->image_path) }}" class="god-image"
                                    alt="{{ $god->name }}" loading="lazy" width="1200" height="800">@else<div
                                        class="image-placeholder" aria-hidden="true">Ω</div>
                                @endif
                                <span class="card-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span
                                    class="card-art-arrow" aria-hidden="true">↗</span>
                            </a>
                            <div class="card-body d-flex flex-column">
                                <p class="god-type">{{ $god->type }}</p>
                                <h3 class="card-title"><a class="js-god-detail" href="{{ route('gods.show', $god) }}">{{ $god->name }}</a>
                                </h3>
                                <p class="card-text">{{ $god->short_description }}</p>
                                <p class="detail-date">Опубликовано: {{ $god->published_at?->format('d.m.Y') ?? 'не указано' }}</p>
                                <div class="card-actions mt-auto"><a class="card-more js-god-detail"
                                        href="{{ route('gods.show', $god) }}" data-edit-url="{{ route('gods.edit', $god) }}" data-delete-url="{{ route('gods.destroy', $god) }}">Быстрый просмотр <span
                                            aria-hidden="true">↗</span></a><a class="card-edit"
                                        href="{{ route('gods.edit', $god) }}"
                                        aria-label="Редактировать: {{ $god->name }}" title="Редактировать">✎</a></div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state"><span aria-hidden="true">Ω</span>
                            <h3>Первая история — за вами</h3>
                            <p>Добавьте бога, чтобы начать свою коллекцию.</p><a class="btn btn-primary"
                                href="{{ route('gods.create') }}">Добавить бога</a>
                        </div>
                    </div>
                @endforelse
            </div>
            <div class="empty-state" id="noResults" hidden><span aria-hidden="true">⌕</span>
                <h3>Ничего не нашлось</h3>
                <p>Попробуйте другое имя или выберите все сферы.</p><button class="btn btn-outline-primary"
                    id="resetFilters" type="button">Сбросить поиск</button>
            </div>
        </section>
    </main>
    <div class="modal fade" id="godModal" tabindex="-1" aria-labelledby="godModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-4" id="godModalTitle">Подробности</h2><button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body" id="godModalBody" aria-live="polite"></div>
                <div class="modal-footer">
                    <div class="modal-navigation me-auto"><button class="btn btn-outline-primary" id="previousGod"
                            type="button" aria-label="Предыдущий бог">←</button><button class="btn btn-outline-primary"
                            id="nextGod" type="button" aria-label="Следующий бог">→</button></div>
                    <a class="btn btn-primary" id="godModalEdit" href="{{ route('gods.index') }}">Редактировать</a>
                    <form id="godModalDeleteForm" action="{{ route('gods.index') }}" method="POST" class="m-0"
                          data-confirm-delete>
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">Удалить</button>
                    </form>
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Закрыть</button>
                    {{-- Совместимость с JS, который был собран в приложенном архиве; визуально ссылки нет. --}}
                    <a id="godFullPage" href="{{ route('gods.index') }}" hidden tabindex="-1" aria-hidden="true"></a>
                </div>
            </div>
        </div>
    </div>
@endsection
