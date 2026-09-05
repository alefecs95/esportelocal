import { ref } from 'vue';

export function useConfirmDialog() {
  const isOpen = ref(false);
  const title = ref('Confirmar ação');
  const message = ref('Deseja continuar?');
  let resolveFn = null;

  const ask = (nextMessage, nextTitle = 'Confirmar ação') => {
    title.value = nextTitle;
    message.value = nextMessage;
    isOpen.value = true;
    return new Promise((resolve) => {
      resolveFn = resolve;
    });
  };

  const confirm = () => {
    isOpen.value = false;
    resolveFn?.(true);
    resolveFn = null;
  };

  const cancel = () => {
    isOpen.value = false;
    resolveFn?.(false);
    resolveFn = null;
  };

  return { isOpen, title, message, ask, confirm, cancel };
}
