@php
    /** @var \App\Models\Content\BlogPost|null $blogPost */
    $blogPost = $blogPost ?? null;
    $isEdit = $blogPost !== null;
@endphp

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-300">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-5">
    <div>
        <label for="title" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Título *</label>
        <input id="title" name="title" type="text" required value="{{ old('title', $blogPost?->title) }}"
               class="w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
    </div>

    <div class="relative z-20">
        <label for="is_published" class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado *</label>
        <select id="is_published" name="is_published" required
                class="relative z-20 w-full rounded border border-border bg-surface px-4 py-2.5 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            @php
                $publishedValue = old('is_published', ($blogPost?->is_published ?? false) ? '1' : '0');
                $publishedValue = in_array((string) $publishedValue, ['1', 'true'], true) ? '1' : '0';
            @endphp
            <option value="0" @selected($publishedValue === '0')>Borrador</option>
            <option value="1" @selected($publishedValue === '1')>Publicado</option>
        </select>
        <p class="mt-1.5 text-xs text-muted">Solo las publicaciones en estado Publicado aparecen en el blog de la tienda.</p>
    </div>

    <div data-blog-image>
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Imagen de portada</label>

        @if ($blogPost?->image)
            <div class="mb-3 flex items-center gap-3 rounded border border-border bg-secondary p-3">
                <img src="{{ $blogPost->image }}" alt="" class="h-16 w-24 rounded object-cover border border-border">
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-text font-semibold">Imagen actual</p>
                    <p class="text-xs text-muted">Se reemplazará si subes otra.</p>
                </div>
                <label class="inline-flex items-center gap-2 text-xs text-red-600 cursor-pointer">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-border-strong bg-surface text-red-500 focus:ring-red-500">
                    Eliminar
                </label>
            </div>
        @endif

        <div data-dropzone class="flex min-h-[140px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-border bg-secondary px-4 py-6 text-center transition-colors hover:border-primary">
            <input id="image" type="file" name="image" accept="image/*" class="sr-only" data-file-input>
            <button type="button" data-dropzone-trigger class="flex flex-col items-center cursor-pointer">
                <svg class="h-8 w-8 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V19a2 2 0 002 2h14a2 2 0 002-2v-2.5M16 8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <p class="mt-3 text-sm text-text-soft">Arrastra una imagen o haz clic para seleccionar</p>
                <p class="mt-1 text-xs text-muted">JPG, PNG, WEBP o GIF — máx. 5 MB</p>
            </button>
            <div data-preview class="mt-4 hidden"></div>
        </div>
    </div>

    <div class="relative z-0">
        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Contenido *</label>
        <input type="hidden" name="body" id="body" value="{{ old('body', $blogPost?->body) }}">
        <div id="blog-editor-wrap" class="relative z-0 overflow-visible rounded border border-border bg-white">
            <div id="blog-editor" class="min-h-[280px] text-text"></div>
        </div>
        <p class="mt-1.5 text-xs text-muted">Usa la barra para negrita, cursiva, tamaño, color y más.</p>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-primary-hover transition-colors">
        {{ $isEdit ? 'Guardar cambios' : 'Crear publicación' }}
    </button>
    <a href="{{ route('admin.blog-posts.index') }}" class="rounded border border-border px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-muted hover:text-text hover:border-border-strong transition-colors">
        Cancelar
    </a>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>
    #blog-editor-wrap .ql-toolbar.ql-snow {
        border: 0;
        border-bottom: 1px solid var(--color-border, #e5e7eb);
        border-radius: 0;
        background: #fafafa;
        position: relative;
        z-index: 2;
    }
    #blog-editor-wrap .ql-container.ql-snow {
        border: 0;
        min-height: 280px;
        font-size: 0.95rem;
        position: relative;
        z-index: 1;
    }
    #blog-editor-wrap .ql-editor { min-height: 260px; }
    #blog-editor-wrap .ql-toolbar .ql-picker-options {
        z-index: 30;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
    (function () {
        const form = document.getElementById('blog-post-form');
        const hidden = document.getElementById('body');
        const editorEl = document.getElementById('blog-editor');
        if (!hidden || !editorEl || typeof Quill === 'undefined') return;

        const quill = new Quill('#blog-editor', {
            theme: 'snow',
            placeholder: 'Escribe el contenido de la publicación…',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    [{ size: ['small', false, 'large', 'huge'] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ color: [] }, { background: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ align: [] }],
                    ['blockquote', 'link'],
                    ['clean'],
                ],
            },
        });

        if (hidden.value) {
            quill.root.innerHTML = hidden.value;
        }

        const sync = () => {
            const html = quill.root.innerHTML;
            hidden.value = (html === '<p><br></p>' || html === '<p></p>') ? '' : html;
        };

        quill.on('text-change', sync);
        form?.addEventListener('submit', sync);

        // Image dropzone
        const root = document.querySelector('[data-blog-image]');
        if (!root) return;
        const zone = root.querySelector('[data-dropzone]');
        const input = root.querySelector('[data-file-input]');
        const preview = root.querySelector('[data-preview]');
        const trigger = root.querySelector('[data-dropzone-trigger]');
        if (!zone || !input || !preview || !trigger) return;

        const highlight = (on) => {
            zone.classList.toggle('border-primary', on);
        };

        const render = () => {
            preview.innerHTML = '';
            const file = input.files?.[0];
            if (!file) {
                preview.classList.add('hidden');
                return;
            }
            const url = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            preview.innerHTML =
                '<div class="inline-flex items-center gap-3 rounded-lg border border-border bg-surface p-2">' +
                '<img src="' + url + '" alt="" class="h-16 w-24 rounded object-cover">' +
                '<div class="text-left"><p class="text-sm text-text font-semibold truncate max-w-[14rem]">' + file.name + '</p>' +
                '<button type="button" data-clear class="mt-1 text-xs font-bold uppercase tracking-wide text-red-600">Quitar</button></div></div>';
            preview.querySelector('[data-clear]')?.addEventListener('click', (e) => {
                e.preventDefault();
                input.value = '';
                render();
            });
        };

        trigger.addEventListener('click', () => input.click());
        ['dragenter', 'dragover'].forEach((n) => zone.addEventListener(n, (e) => { e.preventDefault(); highlight(true); }));
        ['dragleave', 'drop'].forEach((n) => zone.addEventListener(n, (e) => { e.preventDefault(); highlight(false); }));
        zone.addEventListener('drop', (e) => {
            const file = e.dataTransfer?.files?.[0];
            if (!file || !file.type.startsWith('image/')) return;
            const t = new DataTransfer();
            t.items.add(file);
            input.files = t.files;
            render();
        });
        input.addEventListener('change', render);
    })();
</script>
@endpush
