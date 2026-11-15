// Adapt camera photos on the device before uploading them.
export async function prepareReportPhoto(file) {
    const url = URL.createObjectURL(file);
    const image = new Image();
    try {
        await new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = () => reject(new Error('No pudimos abrir la foto. Elige otra imagen.'));
            image.src = url;
        });
        const scale = Math.min(1, 1920 / Math.max(image.naturalWidth, image.naturalHeight));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        const context = canvas.getContext('2d');
        if (!context) throw new Error('No pudimos preparar la foto. Elige otra imagen.');
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        let blob;
        for (const quality of [0.85, 0.7, 0.5, 0.3]) {
            blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', quality));
            if (blob && blob.size <= 1024 * 1024) break;
        }
        if (!blob || blob.size > 1024 * 1024) throw new Error('No pudimos reducir esta foto. Elige otra imagen.');
        return new File([blob], 'foto-reporte.jpg', { type: 'image/jpeg', lastModified: Date.now() });
    } finally {
        URL.revokeObjectURL(url);
        image.src = '';
    }
}
