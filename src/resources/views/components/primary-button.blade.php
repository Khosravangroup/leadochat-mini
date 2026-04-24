<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-3 bg-orange-400 border border-transparent rounded-lg font-black text-xs text-teal-950 uppercase tracking-normal shadow-lg hover:bg-yellow-300 focus:bg-yellow-300 active:bg-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
