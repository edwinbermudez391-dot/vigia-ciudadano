@extends(request()->is('reportes/*') && !auth()->check() ? 'layouts.app' : 'errors::minimal')
@section('title', request()->is('reportes/*') ? 'No encontramos ese número' : __('Not Found'))
@section('code', '404')
@section('message', __('Not Found'))
@section('content')
<div class="citizen-content">
    <header class="citizen-heading"><h1>No encontramos ese número</h1><p>Revisa que esté escrito correctamente. Puedes copiarlo del comprobante e intentarlo otra vez.</p></header>
    <x-report-lookup />
    <a class="btn-secondary" href="{{ route('home') }}">Volver al inicio</a>
</div>
@endsection
