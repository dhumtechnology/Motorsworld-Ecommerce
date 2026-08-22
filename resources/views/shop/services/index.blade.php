{{--
    Servicios / reserva de taller

    Portada: public/images/home/portadas/NUESTROS SERVICIOS.jpg
--}}
@extends('layouts.shop')

@section('title', 'Servicios — '.config('app.name'))

@section('content')
@php
    $bannerPath = public_path('images/home/portadas/NUESTROS SERVICIOS.jpg');
    $banner = file_exists($bannerPath)
        ? asset('images/home/portadas/NUESTROS SERVICIOS.jpg')
        : asset('images/services/banner-servicios.png');
    $field = 'w-full rounded-lg border border-neutral-200 bg-white px-5 py-2.5 text-sm text-neutral-900 shadow-sm transition placeholder:text-neutral-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-400';
    $label = 'mb-2 block text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-500';
    $datetimePart = 'relative z-10 w-full min-w-0 cursor-pointer border-0 bg-transparent px-5 py-3 text-sm text-neutral-900 outline-none focus:ring-0 disabled:cursor-not-allowed disabled:bg-neutral-50 disabled:text-neutral-400';
@endphp

<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[480px] w-full">
        <img
            src="{{ $banner }}"
            alt="Servicios Motoworld"
            class="absolute inset-0 h-full w-full object-cover"
            onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/25 to-black/20"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">Taller Motoworld</p>
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Servicios de taller
            </h1>
            <p class="mt-2 text-sm md:text-base text-white/85 max-w-xl">
                Reserva tu cita de lunes a viernes, de 8:00 a.m. a 6:00 p.m.
            </p>
        </div>
    </div>
</section>

@if ($serviceTypes->isNotEmpty())
    <section class="bg-white border-b border-neutral-100">
        <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-14">
            <div class="mb-8 text-center">
                <h2 class="text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
                    Nuestros servicios
                </h2>
                <p class="mt-2 text-sm text-neutral-500">
                    Conoce lo que hacemos en el taller Motoworld.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($serviceTypes as $type)
                    <article class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm transition hover:shadow-md">
                        <div class="aspect-[4/3] overflow-hidden bg-neutral-100">
                            @if ($type->image)
                                <img
                                    src="{{ $type->image }}"
                                    alt="{{ $type->name }}"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-neutral-200 text-neutral-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="p-5">
                            <h3 class="text-base font-black uppercase tracking-wide text-neutral-900 font-title">
                                {{ $type->name }}
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-600">
                                {{ $type->description ?: 'Consulta disponibilidad y reserva tu cita en el taller.' }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="relative bg-neutral-100">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-neutral-200/60 to-transparent"></div>

    <div class="relative mx-auto max-w-3xl px-4 py-10 md:px-6 md:py-14">
        <div class="mb-8 text-center">
            <h2 class="text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
                Reserva tu servicio
            </h2>
            <p class="mt-2 text-sm text-neutral-500">
                Completa tus datos y elige el horario disponible.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-bold mb-1">Revisa el formulario:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('shop.services.store') }}"
            method="POST"
            class="rounded-2xl border border-neutral-200 bg-white shadow-[0_18px_50px_-28px_rgba(0,0,0,0.35)]"
            x-data="serviceBookingForm(@js([
                'modelsByBrand' => $modelsByBrand,
                'packagesByType' => $packagesByType,
                'slotsUrl' => route('shop.services.slots'),
                'prefill' => $prefill,
                'old' => [
                    'brand_id' => old('brand_id'),
                    'vehicle_model_id' => old('vehicle_model_id'),
                    'service_type_id' => old('service_type_id'),
                    'service_package_id' => old('service_package_id'),
                    'appointment_date' => old('appointment_date'),
                    'appointment_time' => old('appointment_time'),
                ],
            ]))"
            @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
        >
            @csrf

            {{-- Vehículo --}}
            <div class="border-b border-neutral-100 px-5 py-6 md:px-8 md:py-7">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 17h.01M16 17h.01M3 13l1.5-4.5A2 2 0 016.4 7h11.2a2 2 0 011.9 1.5L21 13M5 13h14v4a1 1 0 01-1 1h-1a1 1 0 01-1-1v-1H8v1a1 1 0 01-1 1H6a1 1 0 01-1-1v-4z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-neutral-900">Tu moto</h3>
                        <p class="text-xs text-neutral-500">Marca, modelo y datos del vehículo</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $label }}">Marca *</label>
                        <select name="brand_id" x-model="brandId" @change="onBrandChange()" required class="{{ $field }}">
                            <option value="">Selecciona una marca</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="{{ $label }}">Modelo *</label>
                        <select name="vehicle_model_id" x-model="modelId" required class="{{ $field }}" :disabled="!brandId">
                            <option value="">Selecciona un modelo</option>
                            @foreach ($models as $model)
                                <option
                                    value="{{ $model->id }}"
                                    x-show="String(brandId) === '{{ $model->brand_id }}'"
                                    x-bind:disabled="String(brandId) !== '{{ $model->brand_id }}'"
                                >{{ $model->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="{{ $label }}">Placa *</label>
                        <input type="text" name="plate" value="{{ old('plate') }}" required maxlength="20"
                            class="{{ $field }} uppercase" placeholder="ABC-123">
                    </div>

                    <div>
                        <label class="{{ $label }}">Kilometraje</label>
                        <input type="number" name="km" value="{{ old('km') }}" min="0" step="1"
                            class="{{ $field }}" placeholder="Ej. 15000">
                    </div>
                </div>
            </div>

            {{-- Cliente --}}
            <div class="border-b border-neutral-100 px-5 py-6 md:px-8 md:py-7">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-neutral-900">Tus datos</h3>
                        <p class="text-xs text-neutral-500">Para confirmar y contactarte</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $label }}">Nombres *</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $prefill['first_name']) }}" required maxlength="100"
                            class="{{ $field }}" placeholder="Tus nombres">
                    </div>

                    <div>
                        <label class="{{ $label }}">Apellidos *</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $prefill['last_name']) }}" required maxlength="100"
                            class="{{ $field }}" placeholder="Tus apellidos">
                    </div>

                    <div>
                        <label class="{{ $label }}">DNI *</label>
                        <input type="text" name="customer_document" value="{{ old('customer_document', $prefill['customer_document']) }}" required maxlength="20"
                            class="{{ $field }}">
                    </div>

                    <div>
                        <label class="{{ $label }}">Teléfono *</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $prefill['customer_phone']) }}" required maxlength="30"
                            class="{{ $field }}">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="{{ $label }}">Email *</label>
                        <input type="email" name="customer_email" value="{{ old('customer_email', $prefill['customer_email']) }}" required maxlength="255"
                            class="{{ $field }}">
                    </div>
                </div>
            </div>

            {{-- Servicio --}}
            <div class="border-b border-neutral-100 px-5 py-6 md:px-8 md:py-7">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-neutral-900">Servicio</h3>
                        <p class="text-xs text-neutral-500">Tipo y paquete a realizar</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $label }}">Tipo de servicio *</label>
                        <select name="service_type_id" x-model="serviceTypeId" @change="onServiceTypeChange()" required class="{{ $field }}">
                            <option value="">Selecciona un tipo</option>
                            @foreach ($serviceTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="{{ $label }}">Paquete de servicio *</label>
                        <select name="service_package_id" x-model="packageId" required class="{{ $field }}" :disabled="!serviceTypeId">
                            <option value="">Selecciona un paquete</option>
                            @foreach ($serviceTypes as $type)
                                @foreach ($type->packages as $package)
                                    <option
                                        value="{{ $package->id }}"
                                        x-show="String(serviceTypeId) === '{{ $type->id }}'"
                                        x-bind:disabled="String(serviceTypeId) !== '{{ $type->id }}'"
                                    >
                                        {{ $package->name }}@if($package->price) — S/ {{ number_format((float) $package->price, 2) }}@endif
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Fecha y hora (bloque único) --}}
            <div class="border-b border-neutral-100 px-5 py-6 md:px-8 md:py-7">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-neutral-900">Fecha y hora *</h3>
                        <p class="text-xs text-neutral-500">Solo lunes a viernes · 8:00 a.m. – 6:00 p.m.</p>
                    </div>
                </div>

                <label class="{{ $label }}">Elige fecha y horario</label>
                <div
                    class="grid grid-cols-1 divide-y divide-neutral-200 rounded-lg border border-neutral-200 bg-white shadow-sm transition focus-within:border-orange-500 focus-within:ring-2 focus-within:ring-orange-500/20 sm:grid-cols-2 sm:divide-x sm:divide-y-0"
                    :class="{ 'border-red-300': weekendSelected }"
                >
                    <input
                        type="date"
                        name="appointment_date"
                        x-model="date"
                        @change="onDateChange()"
                        required
                        :min="minDate"
                        class="{{ $datetimePart }} rounded-t-lg sm:rounded-l-lg sm:rounded-tr-none"
                    >
                    <select
                        name="appointment_time"
                        x-model="time"
                        required
                        class="{{ $datetimePart }} rounded-b-lg sm:rounded-r-lg sm:rounded-bl-none"
                        :disabled="!date || loadingSlots || weekendSelected"
                    >
                        <option value="">Horario</option>
                        <template x-for="slot in slots" :key="slot">
                            <option :value="slot" x-text="formatSlot(slot)"></option>
                        </template>
                    </select>
                </div>

                <p class="mt-2 text-[11px] text-red-600" x-show="weekendSelected" x-cloak>
                    Solo puedes reservar de lunes a viernes.
                </p>
                <p class="mt-2 text-[11px] text-neutral-500" x-show="date && !weekendSelected && !loadingSlots && slots.length === 0" x-cloak>
                    No hay horarios disponibles para esta fecha.
                </p>
                <p class="mt-2 text-[11px] text-neutral-500" x-show="loadingSlots" x-cloak>Cargando horarios...</p>
            </div>

            {{-- Comentario + CTA --}}
            <div class="px-5 py-6 md:px-8 md:py-7">
                <div class="mb-5">
                    <label class="{{ $label }}">Comentario</label>
                    <textarea name="comments" rows="3" maxlength="2000"
                        class="{{ $field }}"
                        placeholder="Detalle adicional sobre el servicio...">{{ old('comments') }}</textarea>
                </div>

                <div class="flex justify-center">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-16 py-4 text-sm font-bold uppercase tracking-wider text-white shadow-lg shadow-orange-600/25 transition hover:bg-orange-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:bg-orange-600"
                    >
                        <svg
                            x-show="submitting"
                            x-cloak
                            class="h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="submitting ? 'Reservando…' : 'Reservar cita'">Reservar cita</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<section class="w-full bg-neutral-200" aria-label="Ubicacion Motoworld">
    <div class="relative w-full aspect-[21/9] min-h-[280px] max-h-[480px]">
        <iframe
            title="Mapa Motoworld"
            src="{{ $mapEmbedUrl }}"
            class="absolute inset-0 h-full w-full border-0"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
        ></iframe>
    </div>
</section>

{{-- Productos populares --}}
<x-popular-products :popular-products="$popularProducts" :cart-quantities="$cartQuantities ?? []" />

<script>
    function serviceBookingForm(config) {
        const toIsoDate = (d) => {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + day;
        };

        const nextWeekday = () => {
            const d = new Date();
            d.setHours(12, 0, 0, 0);
            while (d.getDay() === 0 || d.getDay() === 6) {
                d.setDate(d.getDate() + 1);
            }
            return toIsoDate(d);
        };

        const isWeekend = (iso) => {
            if (!iso) return false;
            const d = new Date(iso + 'T12:00:00');
            const day = d.getDay();
            return day === 0 || day === 6;
        };

        return {
            modelsByBrand: config.modelsByBrand || {},
            packagesByType: config.packagesByType || {},
            slotsUrl: config.slotsUrl,
            brandId: config.old.brand_id ? String(config.old.brand_id) : '',
            modelId: config.old.vehicle_model_id ? String(config.old.vehicle_model_id) : '',
            serviceTypeId: config.old.service_type_id ? String(config.old.service_type_id) : '',
            packageId: config.old.service_package_id ? String(config.old.service_package_id) : '',
            date: config.old.appointment_date || '',
            time: config.old.appointment_time || '',
            slots: [],
            loadingSlots: false,
            submitting: false,
            weekendSelected: false,
            minDate: nextWeekday(),
            formatSlot(slot) {
                if (!slot) return '';
                const [h, m] = String(slot).split(':');
                const hour = parseInt(h, 10);
                const suffix = hour >= 12 ? 'p.m.' : 'a.m.';
                const display = ((hour + 11) % 12) + 1;
                return display + ':' + m + ' ' + suffix;
            },
            onBrandChange() {
                this.modelId = '';
            },
            onServiceTypeChange() {
                this.packageId = '';
            },
            onDateChange() {
                this.weekendSelected = isWeekend(this.date);
                if (this.weekendSelected) {
                    this.time = '';
                    this.slots = [];
                    return;
                }
                this.loadSlots();
            },
            async loadSlots() {
                this.time = '';
                this.slots = [];
                if (!this.date || this.weekendSelected) return;
                this.loadingSlots = true;
                try {
                    const response = await fetch(this.slotsUrl + '?date=' + encodeURIComponent(this.date), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    this.slots = data.slots || [];
                } catch (e) {
                    this.slots = [];
                } finally {
                    this.loadingSlots = false;
                }
            },
            init() {
                if (this.date) {
                    this.weekendSelected = isWeekend(this.date);
                    if (!this.weekendSelected) {
                        this.loadSlots().then(() => {
                            if (config.old.appointment_time) {
                                this.time = String(config.old.appointment_time);
                            }
                        });
                    }
                }
            },
        };
    }
</script>
@endsection
