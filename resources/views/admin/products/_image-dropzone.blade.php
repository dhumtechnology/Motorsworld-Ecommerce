@php
    /** @var string $scope 'default' | 'variant' */
    $scope = $scope ?? 'default';
    $isDefault = $scope === 'default';
@endphp

@if ($isDefault)
    <div
        class="space-y-3"
        @dragenter.prevent="defaultDropActive = true"
        @dragover.prevent="defaultDropActive = true"
        @dragleave.prevent="defaultDropActive = false"
        @drop.prevent="onDefaultDrop($event)"
    >
        <input type="file" name="default_images[]" accept="image/*" multiple class="sr-only"
               x-ref="defaultImagesInput" @change="onDefaultInputChange($event)">

        <template x-for="imgId in defaultRemoveImageIds" :key="'rm-d-' + imgId">
            <input type="hidden" name="default_remove_image_ids[]" :value="imgId">
        </template>

        <template x-for="(token, orderIndex) in galleryOrderTokens(defaultGalleryItems, defaultRemoveImageIds)" :key="'ord-d-' + orderIndex + '-' + token">
            <input type="hidden" name="default_image_order[]" :value="token">
        </template>

        <div
            class="rounded-lg border-2 border-dashed px-4 py-8 text-center transition-colors cursor-pointer"
            :class="defaultDropActive ? 'border-primary bg-primary-soft/20' : 'border-border bg-white hover:border-primary/50 hover:bg-primary-soft/10'"
            @click="$refs.defaultImagesInput.click()"
        >
            <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-primary-soft text-primary">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-text">Arrastra imágenes aquí para subir</p>
            <p class="mt-1 text-xs text-muted">o haz clic para seleccionar · arrastra las miniaturas para ordenar · la primera es la principal</p>
        </div>

        <div
            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3"
            x-show="visibleGalleryItems(defaultGalleryItems, defaultRemoveImageIds).length"
        >
            <template x-for="(item, itemIndex) in visibleGalleryItems(defaultGalleryItems, defaultRemoveImageIds)" :key="item.key">
                <div
                    class="relative group rounded-lg border overflow-hidden bg-secondary transition-shadow cursor-grab active:cursor-grabbing"
                    draggable="true"
                    :class="dragOverGalleryKey === item.key ? 'border-primary ring-2 ring-primary/30 shadow-md' : 'border-border'"
                    @dragstart.stop="onGalleryDragStart(item.key)"
                    @dragend="onGalleryDragEnd()"
                    @dragover.prevent="onGalleryDragOver($event, item.key)"
                    @drop.prevent="onGalleryDrop(defaultGalleryItems, item.key)"
                >
                    <img :src="item.type === 'existing' ? item.path : item.url" alt="" class="h-28 w-full object-cover pointer-events-none select-none">
                    <span
                        class="absolute top-1.5 left-1.5 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                        :class="itemIndex === 0 ? 'bg-primary' : 'bg-black/70'"
                        x-text="itemIndex === 0 ? 'Principal' : 'Secundaria'"
                    ></span>
                    <span class="absolute top-1.5 right-1.5 rounded bg-black/60 px-1.5 py-0.5 text-[10px] font-bold text-white" x-text="itemIndex + 1"></span>
                    <div class="absolute inset-x-0 bottom-0 flex">
                        <button
                            type="button"
                            @click.stop="removeDefaultGalleryItem(item)"
                            class="flex-1 bg-black/70 py-1.5 text-[11px] font-bold uppercase tracking-wide text-red-200 hover:text-red-100"
                        >
                            Quitar
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
@else
    <div
        class="space-y-3"
        :data-variant-key="variant._key"
        @dragenter.prevent="variant.dropActive = true"
        @dragover.prevent="variant.dropActive = true"
        @dragleave.prevent="variant.dropActive = false"
        @drop.prevent="onVariantDrop(variant, $event)"
    >
        <input type="file" accept="image/*" multiple class="sr-only"
               :name="`variants[${index}][images][]`"
               @change="onVariantInputChange(variant, $event)">

        <template x-for="imgId in variant.remove_image_ids" :key="'rm-v-' + variant._key + '-' + imgId">
            <input type="hidden" :name="`variants[${index}][remove_image_ids][]`" :value="imgId">
        </template>

        <template x-for="(token, orderIndex) in galleryOrderTokens(variant.galleryItems, variant.remove_image_ids)" :key="'ord-v-' + variant._key + '-' + orderIndex + '-' + token">
            <input type="hidden" :name="`variants[${index}][image_order][]`" :value="token">
        </template>

        <div
            class="rounded-lg border-2 border-dashed px-4 py-8 text-center transition-colors cursor-pointer"
            :class="variant.dropActive ? 'border-primary bg-primary-soft/20' : 'border-border bg-secondary/20 hover:border-primary/50 hover:bg-primary-soft/10'"
            @click="$event.currentTarget.parentElement.querySelector('input[type=file]').click()"
        >
            <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-primary-soft text-primary">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-text">Arrastra imágenes aquí para subir</p>
            <p class="mt-1 text-xs text-muted">o haz clic para seleccionar · arrastra las miniaturas para ordenar · la primera es la principal</p>
        </div>

        <div
            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3"
            x-show="visibleGalleryItems(variant.galleryItems, variant.remove_image_ids).length"
        >
            <template x-for="(item, itemIndex) in visibleGalleryItems(variant.galleryItems, variant.remove_image_ids)" :key="item.key">
                <div
                    class="relative group rounded-lg border overflow-hidden bg-secondary transition-shadow cursor-grab active:cursor-grabbing"
                    draggable="true"
                    :class="dragOverGalleryKey === item.key ? 'border-primary ring-2 ring-primary/30 shadow-md' : 'border-border'"
                    @dragstart.stop="onGalleryDragStart(item.key)"
                    @dragend="onGalleryDragEnd()"
                    @dragover.prevent="onGalleryDragOver($event, item.key)"
                    @drop.prevent="onGalleryDrop(variant.galleryItems, item.key)"
                >
                    <img :src="item.type === 'existing' ? item.path : item.url" alt="" class="h-28 w-full object-cover pointer-events-none select-none">
                    <span
                        class="absolute top-1.5 left-1.5 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                        :class="itemIndex === 0 ? 'bg-primary' : 'bg-black/70'"
                        x-text="itemIndex === 0 ? 'Principal' : 'Secundaria'"
                    ></span>
                    <span class="absolute top-1.5 right-1.5 rounded bg-black/60 px-1.5 py-0.5 text-[10px] font-bold text-white" x-text="itemIndex + 1"></span>
                    <div class="absolute inset-x-0 bottom-0 flex">
                        <button
                            type="button"
                            @click.stop="removeVariantGalleryItem(variant, item)"
                            class="flex-1 bg-black/70 py-1.5 text-[11px] font-bold uppercase tracking-wide text-red-200 hover:text-red-100"
                        >
                            Quitar
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
@endif
