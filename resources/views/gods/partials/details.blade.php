<div class="god-details">
    <div class="detail-image-wrap">
        @if ($god->image_path)
            <img src="{{ asset('storage/' . $god->image_path) }}" class="detail-image" alt="{{ $god->name }}"
            width="1200" height="800">@else<div class="image-placeholder" aria-hidden="true">Ω</div>
        @endif
    </div>
    <div class="detail-copy">
        <p class="eyebrow">{{ $god->type }}</p>
        @if (request()->ajax())
        <h3 class="detail-title">{{ $god->name }}</h3>@else<h1 class="detail-title">{{ $god->name }}</h1>
        @endif
        <p class="detail-intro">{{ $god->short_description }}</p>
        <h2 class="detail-section-title">История и покровительство</h2>
        <p class="preserve-lines">{{ $god->description }}</p>
        <aside class="fact-block"><span class="eyebrow">ЗНАЕТЕ ЛИ ВЫ?</span>
            <p class="mb-0 preserve-lines">{{ $god->fact }}</p>
        </aside>
        <p class="detail-date">Опубликовано: {{ $god->published_at?->format('d.m.Y') ?? 'не указано' }} · Обновлено:
            {{ $god->updated_at?->format('d.m.Y') }}</p>
    </div>
</div>
