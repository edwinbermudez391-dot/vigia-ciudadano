<button type="button" class="btn-secondary public-menu-toggle" data-public-menu aria-controls="public-navigation" aria-expanded="false" aria-label="Abrir menú">
    <x-icon name="menu" /><span class="sr-only">Menú</span>
</button>
<nav id="public-navigation" class="public-navigation" aria-label="Navegación principal">
    <x-nav-link :href="route('home')" :active="request()->routeIs('home')" icon="home">Inicio</x-nav-link>
    <x-nav-link :href="route('reports.create')" :active="request()->routeIs('reports.create')" icon="report">Reportar</x-nav-link>
    <x-nav-link :href="route('reports.device')" :active="request()->routeIs('reports.device')" icon="report">Mis reportes</x-nav-link>
</nav>
