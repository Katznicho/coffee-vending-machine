@props(['url', 'compact' => false])

<div @class([
    'copy-url flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50',
    'px-3 py-2' => ! $compact,
    'px-2 py-1.5' => $compact,
])>
    <code @class([
        'min-w-0 flex-1 break-all font-mono text-gray-800',
        'text-xs' => $compact,
        'text-sm' => ! $compact,
    ])>{{ $url }}</code>
    <button
        type="button"
        data-copy-text="{{ $url }}"
        class="copy-text-btn inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-gray-500 transition hover:bg-white hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand/30"
        aria-label="Copy URL"
        title="Copy URL"
    >
        <svg class="copy-text-btn-icon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <svg class="copy-text-btn-success hidden h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </button>
</div>

@once
    @push('scripts')
        <script>
            if (! window.__copyUrlInitialized) {
                window.__copyUrlInitialized = true;

                document.addEventListener('click', async (event) => {
                    const button = event.target.closest('.copy-text-btn');
                    if (! button) {
                        return;
                    }

                    const text = button.dataset.copyText;
                    const icon = button.querySelector('.copy-text-btn-icon');
                    const success = button.querySelector('.copy-text-btn-success');

                    try {
                        await navigator.clipboard.writeText(text);
                        icon.classList.add('hidden');
                        success.classList.remove('hidden');
                        button.classList.add('bg-green-50');
                        button.setAttribute('aria-label', 'Copied');

                        setTimeout(() => {
                            icon.classList.remove('hidden');
                            success.classList.add('hidden');
                            button.classList.remove('bg-green-50');
                            button.setAttribute('aria-label', 'Copy URL');
                        }, 2000);
                    } catch {
                        button.setAttribute('aria-label', 'Copy failed');
                        setTimeout(() => button.setAttribute('aria-label', 'Copy URL'), 2000);
                    }
                });
            }
        </script>
    @endpush
@endonce
