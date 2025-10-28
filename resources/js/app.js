import './bootstrap';

// Media upload helpers (will noop if page doesn't include elements)
document.addEventListener('DOMContentLoaded', function(){
	try {
		const fileInput = document.getElementById('file-input');
		const uploadForm = document.getElementById('upload-form');
		if(!fileInput || !uploadForm) return;

		// keep behavior minimal here; the blade view also has handlers for preview and upload
		fileInput.addEventListener('change', function(){
			// simple accessibility helper: announce selected file name
			const f = this.files && this.files[0];
			if(f) console.debug('Selected file for upload:', f.name, f.size);
		});
	} catch (e) {
		console.error(e);
	}
});
