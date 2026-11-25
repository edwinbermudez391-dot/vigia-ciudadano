@php
    $user = auth()->user();
    $reportsActive = request()->routeIs('admin.reports', 'reports.*');
@endphp
<aside id="primary-sidebar" class="app-sidebar" aria-label="Menú de trabajo">
    <div class="sidebar-heading"><span class="sidebar-caption">Espacio de trabajo</span><x-icon name="shield" /></div>
    <nav aria-label="Navegación principal" class="sidebar-nav">
        <p class="nav-group-label sidebar-caption">Gestión diaria</p>
        @if ($user->isSuperAdmin())
            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="home">Inicio</x-nav-link>
            <x-nav-link :href="route('admin.reports')" :active="$reportsActive" icon="report">Reportes</x-nav-link>
            <x-nav-link :href="route('admin.contractors')" :active="request()->routeIs('admin.contractors*')" icon="users">Contratistas</x-nav-link>
            <p class="nav-group-label sidebar-caption">Consulta y seguimiento</p>
            <x-nav-link :href="route('admin.analytics')" :active="request()->routeIs('admin.analytics')" icon="chart">Estadísticas</x-nav-link>
        @elseif ($user->isContractor())
            <x-nav-link :href="route('contractor.orders')" :active="request()->routeIs('contractor.*') || request()->routeIs('reports.show')" icon="work">Órdenes de trabajo</x-nav-link>
        @elseif ($user->isInspector())
            <x-nav-link :href="route('inspector.queue')" :active="request()->routeIs('inspector.*')" icon="check">Inspecciones</x-nav-link>
        @endif
        <x-nav-link :href="route('map')" :active="request()->routeIs('map')" icon="map">Mapa</x-nav-link>
    </nav>
    <div class="sidebar-bottom">
        <button type="button" class="btn-secondary sidebar-collapse" data-sidebar-collapse aria-controls="primary-sidebar" aria-expanded="true" aria-label="Contraer menú lateral" title="Contraer menú lateral">
            <x-icon name="collapse" /><span class="sidebar-caption">Contraer menú</span>
        </button>
    </div>
</aside>
