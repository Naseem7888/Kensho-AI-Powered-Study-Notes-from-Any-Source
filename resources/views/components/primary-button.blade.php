<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest 
    bg-indigo-500/80 dark:bg-indigo-600/80 text-white border-2 border-indigo-400/50 dark:border-indigo-500/50 backdrop-blur-sm 
    shadow-lg shadow-indigo-500/50 dark:shadow-indigo-600/50 
    hover:bg-indigo-600/90 dark:hover:bg-indigo-500/90 hover:shadow-xl hover:shadow-indigo-500/60 dark:hover:shadow-indigo-600/60 hover:scale-105 
    focus:bg-indigo-600/90 dark:focus:bg-indigo-500/90 focus:outline-none focus:ring-2 focus:ring-indigo-400/50 dark:focus:ring-indigo-500/50 focus:ring-offset-2 dark:focus:ring-offset-gray-800 
    transition-all ease-in-out duration-150'
]) }}>
    {{ $slot }}
</button>