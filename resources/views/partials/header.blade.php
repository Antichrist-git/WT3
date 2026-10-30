<header class="site-header">
    <nav class="site-container nav-inner" aria-label="Основная навигация">
        <a class="brand" href="{{ route('gods.index') }}" aria-label="Олимп — главная">
            <span class="brand-mark" aria-hidden="true">Ω</span>
            <span>ОЛИМП<small>МИФЫ И ЛЕГЕНДЫ</small></span>
        </a>
        <div class="nav-links">
            <a href="{{ route('gods.index') }}"
                class="nav-catalog {{ request()->routeIs('gods.index') ? 'active' : '' }}"
                @if (request()->routeIs('gods.index')) aria-current="page" @endif>Пантеон</a>
            <a class="btn btn-primary btn-sm" href="{{ route('gods.create') }}"><span aria-hidden="true">+</span>
                Добавить бога</a>
        </div>
    </nav>
</header>
