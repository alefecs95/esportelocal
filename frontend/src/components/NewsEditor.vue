<template>
  <div class="news-editor-wrapper">
    <div class="editor-container border border-gray-200 rounded-xl bg-white shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">
      <!-- Toolbar visual (simulada, pois Editor.js usa inline/block toolbar nativa) -->
      <div class="bg-gray-50 border-b border-gray-200 px-4 py-2 flex items-center gap-2 text-xs text-gray-500">
        <span class="font-medium text-gray-700">Editor Visual</span>
        <span class="w-px h-4 bg-gray-300 mx-2"></span>
        <span>Use <kbd class="bg-white border border-gray-300 rounded px-1 text-[10px]">Tab</kbd> para comandos</span>
        <span>ou clique no <span class="font-bold text-blue-600 text-lg leading-none align-middle">+</span> para adicionar blocos</span>
      </div>
      
      <div id="editorjs" class="prose max-w-none p-6 min-h-[500px] outline-none"></div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue';
import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import List from '@editorjs/list';
import Quote from '@editorjs/quote';
import ImageTool from '@editorjs/image';
import Embed from '@editorjs/embed';
import Table from '@editorjs/table';
import Warning from '@editorjs/warning';
import Delimiter from '@editorjs/delimiter';
import CarouselBlock from '@/utils/editor-blocks/CarouselBlock';
import GalleryBlock from '@/utils/editor-blocks/GalleryBlock';
import api from '../services/api';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  },
  placeholder: {
    type: String,
    default: 'Comece a escrever sua notícia aqui... Digite "/" para abrir o menu de ferramentas.'
  },
  readOnly: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);
const editor = ref(null);

const forceSave = async () => {
  if (!editor.value) {
    return props.modelValue || {};
  }
  const content = await editor.value.save();
  emit('update:modelValue', content);
  return content;
};

defineExpose({
  forceSave
});

const uploadEditorMedia = async (file) => {
  const formData = new FormData();
  formData.append('media', file);
  const response = await api.post('/upload-media', formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  });
  if (response?.data?.success && response?.data?.file?.url) {
    return response.data.file;
  }
  throw new Error('Falha no upload de mídia.');
};

onMounted(() => {
  initEditor();
});

onBeforeUnmount(() => {
  if (editor.value && typeof editor.value.destroy === 'function') {
    editor.value.destroy();
    editor.value = null;
  }
});

const initEditor = () => {
  if (editor.value) return;

  editor.value = new EditorJS({
    holder: 'editorjs',
    placeholder: props.placeholder,
    readOnly: props.readOnly,
    data: props.modelValue && Object.keys(props.modelValue).length ? props.modelValue : undefined,
    minHeight: 400,
    tools: {
      header: {
        class: Header,
        config: {
          placeholder: 'Título da seção',
          levels: [2, 3, 4],
          defaultLevel: 2
        },
        inlineToolbar: true,
        shortcut: 'CMD+SHIFT+H'
      },
      list: {
        class: List,
        inlineToolbar: true,
        config: {
          defaultStyle: 'unordered'
        },
        shortcut: 'CMD+SHIFT+L'
      },
      quote: {
        class: Quote,
        inlineToolbar: true,
        config: {
          quotePlaceholder: 'Digite uma citação importante...',
          captionPlaceholder: 'Autor da citação'
        },
        shortcut: 'CMD+SHIFT+O'
      },
      image: {
        class: ImageTool,
        config: {
          uploader: {
            uploadByFile(file) {
              // Validação de tamanho (20MB)
              if (file.size > 20 * 1024 * 1024) {
                alert('A imagem excede o limite de 20MB.');
                return Promise.resolve({
                  success: 0,
                  message: 'A imagem excede o limite de 20MB.'
                });
              }

              const formData = new FormData();
              formData.append('image', file);
              
              return api.post('/upload-image', formData, {
                headers: {
                  'Content-Type': 'multipart/form-data'
                }
              }).then(response => {
                if (response.data && response.data.success) {
                    return {
                        success: 1,
                        file: {
                            url: response.data.file.url
                        }
                    };
                }
                return {
                    success: 0,
                    message: 'Erro ao processar imagem.'
                };
              }).catch(error => {
                console.error('Erro upload editor:', error);
                return {
                    success: 0,
                    message: 'Falha no upload da imagem.'
                };
              });
            },
            uploadByUrl(url) {
                return Promise.resolve({
                    success: 1,
                    file: {
                        url: url,
                    }
                });
            }
          },
          captionPlaceholder: 'Legenda da imagem'
        }
      },
      embed: {
        class: Embed,
        config: {
          services: {
            youtube: true,
            vimeo: true,
            twitter: true,
            instagram: true
          }
        },
        inlineToolbar: true
      },
      table: {
        class: Table,
        inlineToolbar: true,
        config: {
          rows: 2,
          cols: 3,
        }
      },
      warning: {
        class: Warning,
        inlineToolbar: true,
        config: {
          titlePlaceholder: 'Aviso Importante',
          messagePlaceholder: 'Detalhes do aviso...'
        }
      },
      delimiter: Delimiter,
      carousel: {
        class: CarouselBlock,
        config: {
          uploadMedia: uploadEditorMedia
        }
      },
      gallery: {
        class: GalleryBlock,
        config: {
          uploadMedia: uploadEditorMedia
        }
      }
    },
    onChange: async () => {
      const content = await editor.value.save();
      emit('update:modelValue', content);
    },
    autofocus: !props.readOnly,
    i18n: {
      messages: {
        ui: {
          "blockTunes": {
            "toggler": {
              "Click to tune": "Clique para ajustar",
              "or drag to move": "ou arraste para mover"
            },
          },
          "inlineToolbar": {
            "converter": {
              "Convert to": "Converter para"
            }
          },
          "toolbar": {
            "toolbox": {
              "Add": "Adicionar"
            }
          }
        },
        toolNames: {
          "Text": "Texto",
          "Heading": "Título",
          "List": "Lista",
          "Warning": "Aviso",
          "Checklist": "Checklist",
          "Quote": "Citação",
          "Code": "Código",
          "Delimiter": "Separador",
          "Raw HTML": "HTML Puro",
          "Table": "Tabela",
          "Link": "Link",
          "Marker": "Marcador",
          "Bold": "Negrito",
          "Italic": "Itálico",
          "InlineCode": "Código Inline",
          "Image": "Imagem",
          "Carrossel de Destaques": "Carrossel de Destaques",
          "Galeria de Fotos": "Galeria de Fotos"
        },
        blockTunes: {
          "delete": {
            "Delete": "Excluir"
          },
          "moveUp": {
            "Move up": "Mover para cima"
          },
          "moveDown": {
            "Move down": "Mover para baixo"
          }
        }
      }
    }
  });
};
</script>

<style>
/* Estilização Avançada para o Editor.js */
.codex-editor {
  z-index: 1;
}

.codex-editor__redactor {
  padding-bottom: 50px !important;
  margin-right: 0 !important;
}

/* Tipografia e Espaçamento */
.ce-block__content {
  max-width: 100%;
  margin: 0 auto;
}

.ce-header {
  font-weight: 700;
  margin-bottom: 0.5em;
  color: #1f2937;
}

.ce-paragraph {
  font-size: 1rem;
  line-height: 1.6;
  color: #374151;
}

/* Toolbar de Ações */
.ce-toolbar__content {
  max-width: 100%;
  margin: 0 auto;
}

.ce-toolbar__plus {
  color: #2563eb;
  background-color: #eff6ff;
  border-radius: 6px;
  transition: all 0.2s;
}

.ce-toolbar__plus:hover {
  background-color: #2563eb;
  color: white;
}

.ce-toolbar__settings-btn {
  color: #6b7280;
  border-radius: 6px;
}

.ce-toolbar__settings-btn:hover {
  background-color: #f3f4f6;
  color: #1f2937;
}

/* Blocos Específicos */
.image-tool__image-picture {
  border-radius: 8px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.cdx-quote {
  background-color: #f9fafb;
  border-left: 4px solid #2563eb;
  border-radius: 4px;
  padding: 1rem;
  font-style: italic;
}

.cdx-warning {
  background-color: #fffbeb;
  border: 1px solid #fcd34d;
  border-radius: 8px;
  padding: 1rem;
  color: #92400e;
}

.cdx-warning::before {
  background-color: #f59e0b;
}

.tc-table {
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
}

.tc-row--header {
  background-color: #f9fafb;
  font-weight: 600;
}

/* Popovers e Menus */
.ce-popover {
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.ce-popover__item:hover {
  background-color: #eff6ff;
  color: #2563eb;
}

.ce-popover__item-icon {
  background-color: white;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
  border-radius: 4px;
}

.ce-inline-toolbar {
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.ce-inline-tool:hover {
  background-color: #eff6ff;
  color: #2563eb;
}

.ce-inline-tool--active {
  color: #2563eb;
}
</style>
