{{--
    Panel administrativo — plantilla base para el equipo frontend.

    Variables disponibles:
    - $status (opcional) : mensaje flash de sesión
--}}

<h1>Panel administrativo</h1>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

<p>Motosworld — en construcción.</p>
