<svg viewBox="0 0 200 80" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <filter id="glow">
            <feGaussianBlur stdDeviation="3" result="coloredBlur" />
            <feMerge>
                <feMergeNode in="coloredBlur" />
                <feMergeNode in="SourceGraphic" />
            </feMerge>
        </filter>
    </defs>
    <text x="100" y="50" text-anchor="middle" font-family="system-ui, -apple-system, sans-serif" font-size="42"
        font-weight="700" fill="currentColor" filter="url(#glow)" class="text-indigo-400 dark:text-indigo-300">
        Kensho
    </text>
</svg>