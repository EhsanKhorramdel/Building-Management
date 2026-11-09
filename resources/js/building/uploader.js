export function uploader() {
    const input = document.querySelector('#dropzone-file');
    if (!input) return;
    const image = document.querySelector('#uploaded-image');
    const remove = document.querySelector('.removeFileBtn');
    const label = document.querySelector('.dropzoneFileLabel');
    let preview;
    function update() {
        if (preview) URL.revokeObjectURL(preview);
        const file = input.files[0];
        image.hidden = !file;
        image.classList.toggle('hidden', !file);
        image.parentElement.classList.toggle('hidden', !file);
        label.classList.toggle('hidden', Boolean(file));
        remove.classList.toggle('hidden', !file);
        if (file) {
            preview = URL.createObjectURL(file);
            image.src = preview;
        } else image.removeAttribute('src');
    }
    input.addEventListener('change', update);
    remove.addEventListener('click', () => { input.value = ''; update(); });
}
