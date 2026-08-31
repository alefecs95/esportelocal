class GalleryBlock {
  constructor({ data, config, readOnly }) {
    this.data = data && Array.isArray(data.items) ? data : { items: [] };
    this.config = config || {};
    this.readOnly = !!readOnly;
    this.wrapper = null;
    this.listEl = null;
    this.fileInput = null;
  }

  static get toolbox() {
    return {
      title: 'Galeria de Mídias',
      icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'
    };
  }

  render() {
    this.wrapper = document.createElement('div');
    this.wrapper.className = 'p-4 bg-emerald-50 border border-emerald-200 rounded-xl';

    const header = document.createElement('div');
    header.className = 'flex items-center justify-between gap-2 mb-3';

    const title = document.createElement('div');
    title.className = 'text-sm font-bold text-emerald-800';
    title.textContent = 'Galeria de Mídias';

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold';
    addButton.textContent = 'Adicionar mídias';
    addButton.disabled = this.readOnly;

    this.fileInput = document.createElement('input');
    this.fileInput.type = 'file';
    this.fileInput.accept = 'image/*,video/mp4,video/webm,video/ogg';
    this.fileInput.multiple = true;
    this.fileInput.style.display = 'none';
    this.fileInput.disabled = this.readOnly;

    addButton.addEventListener('click', () => {
      if (!this.readOnly) this.fileInput.click();
    });
    this.fileInput.addEventListener('change', () => this.handleFiles(this.fileInput.files));

    header.appendChild(title);
    header.appendChild(addButton);

    const tip = document.createElement('div');
    tip.className = 'text-xs text-emerald-700 mb-3';
    tip.textContent = 'Aceita fotos e vídeos. Você pode inserir várias mídias e repetir o bloco para criar outra galeria separada.';

    this.listEl = document.createElement('div');
    this.listEl.className = 'grid grid-cols-2 md:grid-cols-4 gap-2';

    this.wrapper.appendChild(header);
    this.wrapper.appendChild(tip);
    this.wrapper.appendChild(this.fileInput);
    this.wrapper.appendChild(this.listEl);

    this.refreshList();
    if (!this.readOnly && this.data.items.length === 0) {
      setTimeout(() => this.fileInput && this.fileInput.click(), 0);
    }

    return this.wrapper;
  }

  async handleFiles(fileList) {
    const files = Array.from(fileList || []);
    if (!files.length) return;
    const uploadMedia = this.config.uploadMedia;
    if (typeof uploadMedia !== 'function') return;

    for (const file of files) {
      const uploaded = await uploadMedia(file);
      if (uploaded?.url) {
        this.data.items.push({
          id: `${Date.now()}-${Math.random()}`,
          type: uploaded.type || (file.type.startsWith('video/') ? 'video' : 'image'),
          url: uploaded.url,
          mime: uploaded.mime || file.type || '',
          name: uploaded.name || file.name || '',
        });
      }
    }
    this.fileInput.value = '';
    this.refreshList();
  }

  refreshList() {
    if (!this.listEl) return;
    this.listEl.innerHTML = '';

    if (!this.data.items.length) {
      const empty = document.createElement('div');
      empty.className = 'col-span-full text-xs text-emerald-700 bg-white border border-emerald-200 rounded-lg px-3 py-2';
      empty.textContent = 'Sem mídias. Clique em "Adicionar mídias".';
      this.listEl.appendChild(empty);
      return;
    }

    this.data.items.forEach((item, index) => {
      const card = document.createElement('div');
      card.className = 'bg-white border border-emerald-200 rounded-lg overflow-hidden';

      const mediaWrap = document.createElement('div');
      mediaWrap.className = 'w-full h-24 bg-black/80 flex items-center justify-center';

      if (item.type === 'video') {
        const video = document.createElement('video');
        video.src = item.url;
        video.controls = true;
        video.className = 'w-full h-full object-cover';
        mediaWrap.appendChild(video);
      } else {
        const img = document.createElement('img');
        img.src = item.url;
        img.className = 'w-full h-full object-cover';
        mediaWrap.appendChild(img);
      }

      const actions = document.createElement('div');
      actions.className = 'flex items-center justify-between p-1.5';

      const badge = document.createElement('span');
      badge.className = 'text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800';
      badge.textContent = item.type === 'video' ? 'Vídeo' : 'Foto';

      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.disabled = this.readOnly;
      removeBtn.className = 'text-[10px] text-red-600 disabled:text-gray-400';
      removeBtn.textContent = 'Remover';
      removeBtn.addEventListener('click', () => {
        this.data.items.splice(index, 1);
        this.refreshList();
      });

      actions.appendChild(badge);
      actions.appendChild(removeBtn);
      card.appendChild(mediaWrap);
      card.appendChild(actions);
      this.listEl.appendChild(card);
    });
  }

  save() {
    return {
      items: this.data.items || []
    };
  }
}

export default GalleryBlock;
