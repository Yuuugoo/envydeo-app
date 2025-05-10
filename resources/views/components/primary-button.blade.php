<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-6 py-2 bg-white border border-transparent rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:text-white hover:bg-gray-700 focus:bg-gray-700 active:bg-green-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
