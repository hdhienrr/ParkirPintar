@if (session('status'))
    <div class="pesan pesan-sukses" role="status"><span class="pesan-ikon" aria-hidden="true">✓</span>{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="pesan pesan-gagal" role="alert">
        <span class="pesan-ikon" aria-hidden="true">!</span>
        <div>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif
