@extends('employee/layouts/base', ['elementActive' => 'settings'])

@section('title', 'Formato de impresión')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-4 min-h-0 overflow-hidden">

    {{-- Header --}}
    <div class="shrink-0">
        <a href="{{ route('employee.settings.index') }}" class="text-xs text-slate-500 hover:text-sky-600">
            <i class="fas fa-arrow-left mr-1"></i> Volver a configuración
        </a>
        <h1 class="text-xl font-bold text-slate-800 mt-1">Formato de impresión</h1>
        <p class="text-sm text-slate-500 mt-0.5">Elige el formato y revisa cómo saldrá el comprobante · Sede: <strong>{{ $branch->name }}</strong></p>
    </div>

    <form method="POST" action="{{ route('employee.settings.format.update') }}" style="flex:1 1 0; min-height:0; display:flex; flex-direction:column;">
        @csrf

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" style="flex:1 1 0; min-height:0; display:flex; flex-direction:column;">
            <div class="p-6" style="flex:1 1 0; min-height:0;">
                {{-- Estilos en línea: Tailwind está compilado y no incluye estas clases --}}
                <div style="display:flex; gap:24px; height:100%; min-height:0;">

                    {{-- Opciones --}}
                    <div style="flex:1 1 280px; min-width:260px; max-width:380px; overflow-y:auto; align-content:start;" class="grid grid-cols-1 gap-3">
                        @foreach($templateLabels as $key => $info)
                        @php $selected = $currentTemplate === $key; @endphp
                        <label class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all
                                      {{ $selected ? 'border-sky-500 bg-sky-50' : 'border-slate-200 hover:border-sky-200 hover:bg-slate-50' }}"
                               id="tpl-label-{{ $key }}">
                            <input type="radio" name="default_template" value="{{ $key }}"
                                   class="mt-0.5 text-sky-600 focus:ring-sky-500"
                                   {{ $selected ? 'checked' : '' }}
                                   onchange="selectTemplate(this)">
                            <div>
                                <div class="flex items-center gap-2">
                                    <i class="fas {{ $info['icon'] }} text-sky-500 text-xs"></i>
                                    <span class="text-sm font-semibold text-slate-700">{{ $info['title'] }}</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $info['desc'] }}</p>
                            </div>
                        </label>
                        @endforeach
                        @error('default_template')
                        <p class="text-red-500 text-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Vista previa --}}
                    <div style="flex:3 1 480px; min-width:300px; min-height:0; display:flex; flex-direction:column;">
                        <p class="text-xs font-semibold text-slate-500 mb-2">
                            Vista previa <span class="font-normal text-slate-400">(con datos de ejemplo)</span>
                        </p>
                        <div id="tpl-preview-box"
                             style="background:#e2e8f0; border-radius:12px; padding:24px; display:flex; justify-content:center; align-items:flex-start; flex:1 1 0; min-height:0; overflow:auto;">
                            <div id="tpl-preview-frame-wrap"
                                 style="position:relative; background:#fff; box-shadow:0 2px 10px rgba(0,0,0,.25); overflow:hidden;">
                                <iframe id="tpl-preview-frame" title="Vista previa"
                                        style="position:absolute; top:0; left:0; border:0; background:#fff; transform-origin:top left;"></iframe>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center gap-3" style="flex-shrink:0;">
                <button type="submit"
                        class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Usar este formato
                </button>
                <a href="{{ route('employee.settings.index') }}"
                   class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-100 transition-colors">
                    Cancelar
                </a>
            </div>
        </div>
    </form>

</div>
@endsection

@section('scripts')
<script>
    // Tamaño real del papel en px (96 dpi)
    const PAPER = {
        ticket_80mm: { w: 302, h: 760 },
        ticket_58mm: { w: 219, h: 760 },
        boleta_a4:   { w: 794, h: 1123 },
        nota_venta:  { w: 559, h: 794 },
    };
    const PREVIEW_URL = @json(route('employee.settings.preview', ['template' => '__T__']));
    // Margen interno (como el @page de cada plantilla), porque en pantalla el navegador no lo aplica
    const PAGE_PADDING = {
        ticket_80mm: '3mm 4mm',
        ticket_58mm: '3mm 4mm',
        boleta_a4:   '15mm',
        nota_venta:  '10mm',
    };

    function showPreview(key) {
        const paper = PAPER[key];
        if (!paper) return;

        const box   = document.getElementById('tpl-preview-box');
        const wrap  = document.getElementById('tpl-preview-frame-wrap');
        const frame = document.getElementById('tpl-preview-frame');

        // Escala solo por ancho: el recuadro hace scroll vertical si el papel es más alto
        const available = box.clientWidth - 48 - 17; // padding + barra de scroll
        const scale = Math.min(1.25, available / paper.w);

        wrap.style.width   = (paper.w * scale) + 'px';
        wrap.style.height  = (paper.h * scale) + 'px';
        frame.style.width  = paper.w + 'px';
        frame.style.height = paper.h + 'px';
        frame.style.transform = 'scale(' + scale + ')';

        // Oculta los botones de imprimir/cerrar dentro de la vista previa
        frame.onload = function () {
            try {
                const doc = frame.contentDocument;
                doc.querySelectorAll('.no-print').forEach(el => el.style.display = 'none');
                doc.documentElement.style.boxSizing = 'border-box';
                doc.documentElement.style.padding = PAGE_PADDING[key] || '0';
            } catch (e) {}
        };
        frame.src = PREVIEW_URL.replace('__T__', key);
    }

    function selectTemplate(radio) {
        showPreview(radio.value);
        document.querySelectorAll('[id^="tpl-label-"]').forEach(label => {
            label.classList.remove('border-sky-500', 'bg-sky-50');
            label.classList.add('border-slate-200');
        });
        const selected = document.getElementById('tpl-label-' + radio.value);
        if (selected) {
            selected.classList.add('border-sky-500', 'bg-sky-50');
            selected.classList.remove('border-slate-200');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const checked = document.querySelector('input[name="default_template"]:checked');
        if (checked) showPreview(checked.value);
    });
</script>
@endsection
