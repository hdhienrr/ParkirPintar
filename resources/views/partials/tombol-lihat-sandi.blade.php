{{--
    Tombol ikon mata untuk menampilkan atau menyembunyikan password.
    Parameter: $untuk (id isian password). Perilakunya diatur skrip di auth/kerangka.
--}}
<button type="button" class="tombol-mata" data-lihat-sandi="{{ $untuk }}" aria-pressed="false" aria-label="Tampilkan password" title="Tampilkan password">
    <svg class="ikon-buka" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/>
        <circle cx="12" cy="12" r="3"/>
    </svg>
    <svg class="ikon-tutup" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 3l18 18"/>
        <path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c6.4 0 10 7 10 7a17.5 17.5 0 0 1-3.2 4.1"/>
        <path d="M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7a10.5 10.5 0 0 0 5.4-1.6"/>
        <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
    </svg>
</button>
