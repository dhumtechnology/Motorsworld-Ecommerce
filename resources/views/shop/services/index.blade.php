{{--
    Servicios / reserva de taller

    Banner: public/images/services/banner-servicios.png
--}}
@extends('layouts.shop')

@section('title', 'Servicios — '.config('app.name'))

@section('content')
@php
    $banner = asset('images/services/banner-servicios.png');
@endphp

<section class="relative w-full overflow-hidden bg-neutral-900">
    <div class="relative aspect-[21/9] min-h-[220px] max-h-[480px] w-full">
        <img
            src="{{ $banner }}"
            alt="Servicios Motosworld"
            class="absolute inset-0 h-full w-full object-cover"
            onerror="this.classList.add('opacity-0'); this.parentElement.classList.add('bg-neutral-800');"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20"></div>
        <div class="absolute inset-x-0 bottom-0 p-6 md:p-10">
            <h1 class="text-2xl md:text-4xl font-black uppercase tracking-wide text-white font-title">
                Servicios de taller
            </h1>
            <p class="mt-2 text-sm md:text-base text-white/80 max-w-xl">
                Reserva tu cita de lunes a domingo, de 8:00 a.m. a 6:00 p.m.
            </p>
        </div>
    </div>
</section>

<section class="bg-white">
    <div class="mx-auto max-w-[95%] px-4 md:px-8 py-10 md:py-14">
        <h2 class="mb-8 text-xl md:text-2xl font-black uppercase tracking-[0.12em] text-neutral-900 font-title">
            Reserva tu servicio
        </h2>

        @if (session('status'))
            <div class="mb-6 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
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
            class="grid grid-cols-1 md:grid-cols-2 gap-5"
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
        >
            @csrf

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Marca *</label>
                <select name="brand_id" x-model="brandId" @change="onBrandChange()" required
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500">
                    <option value="">Selecciona una marca</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Modelo *</label>
                <select name="vehicle_model_id" x-model="modelId" required
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500"
                    :disabled="!brandId">
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
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Placa *</label>
                <input type="text" name="plate" value="{{ old('plate') }}" required maxlength="20"
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm uppercase focus:outline-none focus:border-orange-500"
                    placeholder="ABC-123">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Kilometraje</label>
                <input type="number" name="km" value="{{ old('km') }}" min="0" step="1"
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500"
                    placeholder="Ej. 15000">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Nombres y apellidos *</label>
                <input type="text" name="customer_name" value="{{ old('customer_name', $prefill['customer_name']) }}" required maxlength="150"
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">DNI *</label>
                <input type="text" name="customer_document" value="{{ old('customer_document', $prefill['customer_document']) }}" required maxlength="20"
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Teléfono *</label>
                <input type="text" name="customer_phone" value="{{ old('customer_phone', $prefill['customer_phone']) }}" required maxlength="30"
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Email *</label>
                <input type="email" name="customer_email" value="{{ old('customer_email', $prefill['customer_email']) }}" required maxlength="255"
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Tipo de servicio *</label>
                <select name="service_type_id" x-model="serviceTypeId" @change="onServiceTypeChange()" required
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500">
                    <option value="">Selecciona un tipo</option>
                    @foreach ($serviceTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Paquete de servicio *</label>
                <select name="service_package_id" x-model="packageId" required
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500"
                    :disabled="!serviceTypeId">
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

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Fecha *</label>
                <input type="date" name="appointment_date" x-model="date" @change="loadSlots()" required
                    :min="minDate"
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Hora *</label>
                <select name="appointment_time" x-model="time" required
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500"
                    :disabled="!date || loadingSlots">
                    <option value="">Selecciona un horario</option>
                    <template x-for="slot in slots" :key="slot">
                        <option :value="slot" x-text="slot"></option>
                    </template>
                </select>
                <p class="mt-1 text-[11px] text-neutral-500" x-show="date && !loadingSlots && slots.length === 0">
                    No hay horarios disponibles para esta fecha.
                </p>
                <p class="mt-1 text-[11px] text-neutral-500" x-show="loadingSlots">Cargando horarios...</p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-2">Comentario</label>
                <textarea name="comments" rows="4" maxlength="2000"
                    class="w-full rounded border border-neutral-300 px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500"
                    placeholder="Detalle adicional sobre el servicio...">{{ old('comments') }}</textarea>
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded bg-orange-600 px-8 py-3 text-sm font-bold uppercase tracking-wider text-white hover:bg-orange-500 transition-colors">
                    Reservar cita
                </button>
            </div>
        </form>
    </div>
</section>

<section class="w-full bg-neutral-200" aria-label="Ubicacion Motosworld">
    <div class="relative w-full aspect-[21/9] min-h-[280px] max-h-[480px]">
        <iframe
            title="Mapa Motosworld"
            src="{{ $mapEmbedUrl }}"
            class="absolute inset-0 h-full w-full border-0"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
        ></iframe>
    </div>
</section>

@include('shop.partials.popular-products-carousel')

<script>
    function serviceBookingForm(config) {
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
            minDate: new Date().toISOString().slice(0, 10),
            get models() {
                return this.modelsByBrand[this.brandId] || [];
            },
            get packages() {
                return this.packagesByType[this.serviceTypeId] || [];
            },
            onBrandChange() {
                this.modelId = '';
            },
            onServiceTypeChange() {
                this.packageId = '';
            },
            async loadSlots() {
                this.time = '';
                this.slots = [];
                if (!this.date) return;
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
                    this.loadSlots().then(() => {
                        if (config.old.appointment_time) {
                            this.time = String(config.old.appointment_time);
                        }
                    });
                }
            },
        };
    }
</script>
@endsection
