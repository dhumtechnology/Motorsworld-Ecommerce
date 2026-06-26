@props([
    'title',
    'options' => [],
    'name',
    'selected' => [] // Ahora por defecto es un array vacío
])

<div class="bg-[#1e1e1e] p-6 rounded-md border border-neutral-800 text-white select-none">
    <h3 class="font-sans font-black tracking-wider uppercase text-xl text-white mb-4 antialiased">
        {{ $title }}
    </h3>

    <ul class="space-y-3">
        @foreach($options as $option)
            @php
                $id = $option->id;
                $label = $option->name; 
                
                // Convertimos a array por seguridad y verificamos si este ID está entre los seleccionados
                $selectedArray = is_array($selected) ? $selected : ($selected ? [$selected] : []);
                $isChecked = in_array($id, $selectedArray);
            @endphp
            <li>
                <label class="flex items-center gap-3 group cursor-pointer text-white font-bold text-sm tracking-wide">
                    <input type="checkbox" 
                           name="{{ $name }}[]" 
                           value="{{ $id }}" 
                           class="sr-only peer"
                           {{ $isChecked ? 'checked' : '' }}
                           onchange="this.form.submit()">
                    
                    <span class="w-4 h-4 bg-transparent border-2 border-neutral-400 rounded-sm transition-all duration-150 shrink-0
                        peer-checked:bg-orange-600 peer-checked:border-orange-600 group-hover:border-orange-500">
                    </span>
                    
                    <span class="text-gray-300 group-hover:text-white transition-colors duration-150 font-sans normal-case">
                        {{ $label }}
                    </span>
                </label>
            </li>
        @endforeach
    </ul>
</div>