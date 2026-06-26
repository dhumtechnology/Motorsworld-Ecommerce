@props([
    'title' => 'CATEGORÍAS',
    'options' => []
])

<div class="bg-[#1e1e1e] p-6 rounded-md border border-neutral-800 text-white select-none">
    <h3 class="font-sans font-black tracking-wider uppercase text-2xl md:text-3xl text-white mb-6 antialiased">
        {{ $title }}
    </h3>

    <ul class="space-y-4">
        @foreach($options as $id => $label)
            <li>
                <label class="flex items-center gap-4 group cursor-pointer text-white font-bold text-lg md:text-xl tracking-wide">
                    <input type="checkbox" name="categories[]" value="{{ $id }}" class="sr-only peer">
                    
                    <span class="w-6 h-4 bg-transparent border-2 border-neutral-400 rounded-sm transition-all duration-150 shrink-0
                        peer-checked:bg-orange-600 peer-checked:border-orange-600 group-hover:border-orange-500">
                    </span>
                    
                    <span class="text-gray-200 group-hover:text-white transition-colors duration-150 font-sans">
                        {{ $label }}
                    </span>
                </label>
            </li>
        @endforeach
    </ul>
</div>