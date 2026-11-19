(function () {
  const form = document.getElementById('vy-profile-form');
  const input = document.getElementById('avatar');
  const status = document.getElementById('vy-photo-status');
  if (!form || !input || !window.DataTransfer || !window.createImageBitmap) return;
  let busy = false; let generation = 0;
  form.addEventListener('submit', function (event) { if (busy) { event.preventDefault(); status.textContent = 'Please wait while your photo is prepared.'; } });
  input.addEventListener('change', async function () {
    const current = ++generation;
    const file = input.files[0]; if (!file) { busy = false; status.textContent = ''; return; }
    if (!['image/jpeg','image/png','image/webp'].includes(file.type)) { busy = false; input.value = ''; status.textContent = 'Choose a JPEG, PNG or WebP image.'; return; }
    if (file.size > 20 * 1024 * 1024) { busy = false; input.value = ''; status.textContent = 'Choose a photo under 20 MB.'; return; }
    busy = true; status.textContent = 'Preparing photo…';
    try {
      const bitmap = await createImageBitmap(file);
      if (bitmap.width * bitmap.height > 40000000) { bitmap.close(); throw new Error('Image dimensions are too large.'); }
      const canvas = document.createElement('canvas'); canvas.width = canvas.height = 160;
      const ctx = canvas.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0,0,160,160);
      const size = Math.min(bitmap.width,bitmap.height); ctx.drawImage(bitmap,(bitmap.width-size)/2,(bitmap.height-size)/2,size,size,0,0,160,160); bitmap.close();
      const blob = await new Promise(resolve => canvas.toBlob(resolve,'image/jpeg',0.8));
      if (!blob || blob.size > 1048576) throw new Error('Unable to resize photo.');
      if (current !== generation) return;
      const transfer = new DataTransfer(); transfer.items.add(new File([blob], 'avatar.jpg', {type:'image/jpeg'})); input.files = transfer.files;
      status.textContent = 'Photo ready to save.';
    } catch (_) { if (current === generation) { input.value = ''; status.textContent = 'Could not prepare this image. Please choose another.'; } }
    finally { if (current === generation) busy = false; }
  });
})();
