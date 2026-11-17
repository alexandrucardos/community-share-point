/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './stimulus_bootstrap.js';

let previewObjectUrl;

document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.id !== 'image') {
        return;
    }

    const selectedFile = input.files?.[0];
    const preview = document.getElementById('image-preview');
    if (!selectedFile || !(preview instanceof HTMLImageElement)) {
        return;
    }

    if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
    }

    previewObjectUrl = URL.createObjectURL(selectedFile);
    preview.src = previewObjectUrl;
    preview.hidden = false;
});
