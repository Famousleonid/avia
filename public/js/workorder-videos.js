document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('photoPage');
    const list = document.getElementById('workorderVideoList');
    if (!page || !list) return;
    const id = encodeURIComponent(page.dataset.workorderId);
    const status = document.getElementById('workorderVideoProgress');
    const file = document.getElementById('workorderVideoFile');
    const capture = document.getElementById('workorderVideoCapture');
    const add = document.getElementById('addWorkorderVideo');
    const record = document.getElementById('recordWorkorderVideo');
    let uploading = false;
    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    async function load() {
        const response = await fetch(`/workorders/${id}/pdfs`, {headers:{Accept:'application/json'}});
        if (!response.ok) throw new Error('Unable to load videos. Refresh the page to retry.');
        const data = await response.json();
        const videos = (data.pdfs || []).filter(item => item.kind === 'video');
        list.querySelectorAll('video').forEach(video => video.pause());
        list.innerHTML = videos.length ? videos.map(item => `
            <div class="col-12 col-md-6 col-xl-4">
                <div class="border rounded p-2 h-100">
                    <div class="small text-break mb-2">${escape(item.name || item.file_name)}</div>
                    <video controls playsinline preload="none" class="w-100 d-block rounded" style="aspect-ratio:16/9;background:#000;object-fit:contain" src="${escape(item.url)}"></video>
                    <div class="small text-muted video-playback-error d-none">Cannot play this format in this browser. Download to open it.</div>
                    <a class="btn btn-sm btn-outline-info mt-2" href="${escape(item.download_url)}" download>Download</a>
                </div>
            </div>`).join('') : '<div class="col-12 text-muted small">No videos</div>';
        list.querySelectorAll('video').forEach(video => {
            video.addEventListener('error', () => video.nextElementSibling.classList.remove('d-none'));
            video.addEventListener('play', () => list.querySelectorAll('video').forEach(other => { if (other !== video) other.pause(); }));
        });
    }

    function upload(input) {
        const video = input.files?.[0];
        if (!video || uploading) return;
        if (!/\.(mp4|mov|webm)$/i.test(video.name) || video.size > 50 * 1024 * 1024) {
            status.textContent = 'Select an MP4, MOV or WebM video up to 50 MB.';
            input.value = '';
            return;
        }
        uploading = true;
        [add, record, file, capture].forEach(el => el.disabled = true);
        status.textContent = 'Uploading 0%';
        const request = new XMLHttpRequest();
        const data = new FormData();
        data.append('pdf', video);
        data.append('doc_kind', 'video');
        request.upload.onprogress = event => { if (event.lengthComputable) status.textContent = `Uploading ${Math.round(event.loaded / event.total * 100)}%`; };
        const finish = () => {
            uploading = false;
            [add, record, file, capture].forEach(el => el.disabled = false);
            input.value = '';
        };
        request.onload = async () => {
            try {
                if (request.status < 200 || request.status >= 300) {
                    let message = 'Video upload failed.';
                    try { message = JSON.parse(request.responseText).message || message; } catch (_) {}
                    throw new Error(message);
                }
                status.textContent = 'Video uploaded.';
                await load();
            } catch (error) { status.textContent = error.message; }
            finally { finish(); }
        };
        request.onerror = () => { status.textContent = 'Upload failed. Check your connection and try again.'; finish(); };
        request.onabort = () => { status.textContent = 'Upload cancelled.'; finish(); };
        request.open('POST', `/workorders/pdf/${id}`);
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
        request.send(data);
    }
    add.addEventListener('click', () => file.click());
    record.addEventListener('click', () => capture.click());
    file.addEventListener('change', () => upload(file));
    capture.addEventListener('change', () => upload(capture));
    load().catch(error => { list.textContent = error.message; });
});
