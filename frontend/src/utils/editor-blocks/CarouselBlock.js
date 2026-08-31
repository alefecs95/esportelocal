class CarouselBlock {
  constructor({ data, config, readOnly }) {
    this.data = data && Array.isArray(data.items) ? data : { items: [] };
    this.config = config || {};
    this.readOnly = !!readOnly;
    this.wrapper = null;
    this.listEl = null;
    this.fileInput = null;
    this.activeIndex = 0;
  }

  static get toolbox() {
    return {
      title: 'Carrossel de Mídias',
      icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 3v18"/><path d="M17 3v18"/></svg>'
    };
  }

  render() {
    this.wrapper = document.createElement('div');
    this.wrapper.className = 'p-4 bg-blue-50 border border-blue-200 rounded-xl';

    const header = document.createElement('div');
    header.className = 'flex items-center justify-between gap-2 mb-3';

    const title = document.createElement('div');
    title.className = 'text-sm font-bold text-blue-800';
    title.textContent = 'Carrossel de Mídias';

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold';
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
    tip.className = 'text-xs text-blue-700 mb-3';
    tip.textContent = 'Aceita fotos e vídeos. Adicione um novo bloco de carrossel quando quiser outro conjunto separado de mídias.';

    this.listEl = document.createElement('div');
    this.listEl.className = 'space-y-2';

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
      empty.className = 'text-xs text-blue-700 bg-white border border-blue-200 rounded-lg px-3 py-2';
      empty.textContent = 'Sem mídias. Clique em "Adicionar mídias".';
      this.listEl.appendChild(empty);
      return;
    }

    this.activeIndex = Math.max(0, Math.min(this.activeIndex, this.data.items.length - 1));
    const current = this.data.items[this.activeIndex];
    const row = document.createElement('div');
    row.className = 'bg-white border border-blue-200 rounded-lg overflow-hidden';

    const mediaWrap = document.createElement('div');
    mediaWrap.className = 'relative w-full h-56 bg-black/80 flex items-center justify-center';

    if (current.type === 'video') {
      const video = document.createElement('video');
      video.src = current.url;
      video.controls = true;
      video.className = 'w-full h-full object-contain';
      mediaWrap.appendChild(video);
    } else {
      const img = document.createElement('img');
      img.src = current.url;
      img.className = 'w-full h-full object-contain';
      mediaWrap.appendChild(img);
    }

    if (this.data.items.length > 1) {
      const prev = document.createElement('button');
      prev.type = 'button';
      prev.className = 'absolute left-2 top-1/2 -translate-y-1/2 px-2 py-1 rounded bg-white/90 text-gray-700 text-sm';
      prev.textContent = '←';
      prev.addEventListener('click', () => {
        this.activeIndex = this.activeIndex === 0 ? this.data.items.length - 1 : this.activeIndex - 1;
        this.refreshList();
      });

      const next = document.createElement('button');
      next.type = 'button';
      next.className = 'absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 rounded bg-white/90 text-gray-700 text-sm';
      next.textContent = '→';
      next.addEventListener('click', () => {
        this.activeIndex = this.activeIndex === this.data.items.length - 1 ? 0 : this.activeIndex + 1;
        this.refreshList();
      });

      mediaWrap.appendChild(prev);
      mediaWrap.appendChild(next);
    }

    const actions = document.createElement('div');
    actions.className = 'flex items-center justify-between p-2';

    const left = document.createElement('div');
    left.className = 'flex items-center gap-2';

    const badge = document.createElement('span');
    badge.className = 'text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800';
    badge.textContent = current.type === 'video' ? 'Vídeo' : 'Foto';

    const pos = document.createElement('span');
    pos.className = 'text-[10px] text-gray-500';
    pos.textContent = `${this.activeIndex + 1} de ${this.data.items.length}`;

    left.appendChild(badge);
    left.appendChild(pos);

    const controls = document.createElement('div');
    controls.className = 'flex items-center gap-2';

    const up = document.createElement('button');
    up.type = 'button';
    up.textContent = '↑';
    up.className = 'text-xs px-2 py-1 rounded bg-gray-100 disabled:text-gray-300';
    up.disabled = this.readOnly || this.activeIndex === 0;
    up.addEventListener('click', () => {
      const aux = this.data.items[this.activeIndex - 1];
      this.data.items[this.activeIndex - 1] = this.data.items[this.activeIndex];
      this.data.items[this.activeIndex] = aux;
      this.activeIndex -= 1;
      this.refreshList();
    });

    const down = document.createElement('button');
    down.type = 'button';
    down.textContent = '↓';
    down.className = 'text-xs px-2 py-1 rounded bg-gray-100 disabled:text-gray-300';
    down.disabled = this.readOnly || this.activeIndex === this.data.items.length - 1;
    down.addEventListener('click', () => {
      const aux = this.data.items[this.activeIndex + 1];
      this.data.items[this.activeIndex + 1] = this.data.items[this.activeIndex];
      this.data.items[this.activeIndex] = aux;
      this.activeIndex += 1;
      this.refreshList();
    });

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.disabled = this.readOnly;
    removeBtn.className = 'text-xs text-red-600 disabled:text-gray-400';
    removeBtn.textContent = 'Remover';
    removeBtn.addEventListener('click', () => {
      this.data.items.splice(this.activeIndex, 1);
      this.activeIndex = Math.max(0, this.activeIndex - 1);
      this.refreshList();
    });

    controls.appendChild(up);
    controls.appendChild(down);
    controls.appendChild(removeBtn);
    actions.appendChild(left);
    actions.appendChild(controls);
    row.appendChild(mediaWrap);
    row.appendChild(actions);

    if (this.data.items.length > 1) {
      const thumbs = document.createElement('div');
      thumbs.className = 'flex gap-2 overflow-x-auto p-2 border-t border-blue-100';
      this.data.items.forEach((entry, idx) => {
        const thumbBtn = document.createElement('button');
        thumbBtn.type = 'button';
        thumbBtn.className = `relative w-16 h-12 rounded overflow-hidden border ${idx === this.activeIndex ? 'border-blue-600' : 'border-gray-200'}`;
        thumbBtn.addEventListener('click', () => {
          this.activeIndex = idx;
          this.refreshList();
        });

        if (entry.type === 'video') {
          const v = document.createElement('video');
          v.src = entry.url;
          v.muted = true;
          v.className = 'w-full h-full object-cover';
          thumbBtn.appendChild(v);
        } else {
          const i = document.createElement('img');
          i.src = entry.url;
          i.className = 'w-full h-full object-cover';
          thumbBtn.appendChild(i);
        }

        thumbs.appendChild(thumbBtn);
      });
      row.appendChild(thumbs);
    }

    this.listEl.appendChild(row);
  }

  save() {
    return {
      items: this.data.items || []
    };
  }
}

export default CarouselBlock;
