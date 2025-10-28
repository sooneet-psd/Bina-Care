<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Media Library - Admin</title>
    @if (file_exists(base_path('public/mix-manifest.json')) || true)
        @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @endif
    <style>
        /* Minimal inline styles so view looks reasonable even before building assets */
        body { font-family: Arial, Helvetica, sans-serif; padding: 24px; background:#f8fafc }
        .card{background:white;padding:16px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.06)}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px}
        .thumb{height:120px;object-fit:cover;border-radius:6px;width:100%}
    </style>
</head>
<body>
    <h1>Media Library</h1>

    @if(session('success'))
        <div style="margin:12px 0;padding:10px;background:#e6fffa;border-radius:6px">{{ session('success') }}</div>
    @endif

    <div class="card" style="margin-bottom:16px">
        <form id="upload-form" action="{{ url('admin/media') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <input id="file-input" type="file" name="file" accept="image/*,video/*,application/pdf" />
                <button class="btn btn-primary" type="submit">Upload</button>
                <div id="progress" style="flex:1;display:none">
                    <progress id="upload-progress" value="0" max="100" style="width:100%"></progress>
                </div>
            </div>
        </form>
        <div id="preview" style="margin-top:12px"></div>
    </div>

    <h2>Uploaded files</h2>
    <div class="grid">
        @foreach($media as $m)
            <div class="card">
                @if(str_starts_with($m->mime_type, 'image'))
                    @php $thumbUrl = $m->thumbnail_path ? Storage::disk('public')->url($m->thumbnail_path) : Storage::disk('public')->url($m->path); @endphp
                    <img src="{{ $thumbUrl }}" alt="{{ $m->filename }}" class="thumb">
                @else
                    <div style="height:120px;display:flex;align-items:center;justify-content:center;background:#f1f5f9;border-radius:6px">{{ strtoupper(pathinfo($m->filename, PATHINFO_EXTENSION)) }}</div>
                @endif

                <div style="margin-top:8px">
                    <div style="font-weight:600">{{ $m->filename }}</div>
                    <div style="color:#64748b;font-size:13px">{{ round($m->size / 1024, 2) }} KB • {{ $m->mime_type }}</div>
                </div>

                <form style="margin-top:8px" method="post" action="{{ url('admin/media', $m->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="background:#ef4444;color:white;border:none;padding:8px 10px;border-radius:6px;cursor:pointer">Delete</button>
                </form>
            </div>
        @endforeach
    </div>

    <div style="margin-top:16px">{{ $media->links() }}</div>

    <script>
        // Basic preview + AJAX upload with progress (falls back to normal submit)
        (function(){
            const form = document.getElementById('upload-form');
            const input = document.getElementById('file-input');
            const preview = document.getElementById('preview');
            const progressWrap = document.getElementById('progress');
            const progressEl = document.getElementById('upload-progress');

            input && input.addEventListener('change', function(){
                preview.innerHTML = '';
                const file = this.files && this.files[0];
                if(!file) return;
                if(file.type.startsWith('image')){
                    const img = document.createElement('img');
                    img.style.maxHeight = '160px';
                    img.style.borderRadius = '6px';
                    img.src = URL.createObjectURL(file);
                    preview.appendChild(img);
                } else {
                    preview.textContent = file.name + ' (' + Math.round(file.size/1024) + ' KB)';
                }
            });

            form && form.addEventListener('submit', function(e){
                // Attempt AJAX upload with progress
                e.preventDefault();
                const f = input.files && input.files[0];
                if(!f) return alert('Please choose a file to upload.');

                const fd = new FormData();
                fd.append('file', f);
                fd.append('_token', '{{ csrf_token() }}');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);
                xhr.upload.onprogress = function(ev){
                    if(ev.lengthComputable){
                        const pct = Math.round((ev.loaded / ev.total) * 100);
                        progressWrap.style.display = 'block';
                        progressEl.value = pct;
                    }
                };
                xhr.onreadystatechange = function(){
                    if(xhr.readyState === 4){
                        if(xhr.status >= 200 && xhr.status < 300){
                            location.reload();
                        } else {
                            alert('Upload failed');
                        }
                    }
                };
                xhr.send(fd);
            });
        })();
    </script>
</body>
</html>
