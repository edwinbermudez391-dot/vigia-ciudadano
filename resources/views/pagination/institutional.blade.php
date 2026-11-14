@if ($paginator->hasPages())
<nav role="navigation" aria-label="Paginación" class="pagination-bar">
    <p>Mostrando <strong>{{ $paginator->firstItem() }}</strong> a <strong>{{ $paginator->lastItem() }}</strong> de <strong>{{ $paginator->total() }}</strong> resultados</p>
    <div class="pagination-links">
        @if ($paginator->onFirstPage())<span class="pagination-disabled" aria-disabled="true">Anterior</span>
        @else<a class="btn-secondary" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>@endif
        <span class="pagination-current" aria-current="page">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())<a class="btn-secondary" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
        @else<span class="pagination-disabled" aria-disabled="true">Siguiente</span>@endif
    </div>
</nav>
@endif
