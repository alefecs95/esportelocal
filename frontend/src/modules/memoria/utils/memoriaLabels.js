export const inputClass =
  'w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-11 text-gray-900 placeholder:text-gray-400';

export const papelLabels = {
  atleta: 'Atleta',
  tecnico: 'Técnico',
  dirigente: 'Dirigente',
  outro: 'Outra atuação',
};

export const tipoMarcoLabels = {
  conquista: 'Conquista',
  campeonato: 'Campeonato',
  inauguracao: 'Inauguração',
  atleta: 'Pessoa em destaque',
  marco: 'Outro fato',
};

export const tipoAcervoLabels = {
  foto: 'Foto',
  video: 'Vídeo',
  documento: 'Documento',
};

export const tipoLocalLabels = {
  ginasio: 'Ginásio',
  estadio: 'Estádio',
  quadra: 'Quadra',
  pista: 'Pista',
  outro: 'Outro',
};

export const originLabels = {
  curadoria: 'Prefeitura',
  comunidade: 'Comunidade',
};

export function detectAcervoTipo(file) {
  if (!file?.type) return 'foto';
  if (file.type.startsWith('video/')) return 'video';
  if (file.type === 'application/pdf' || file.type.includes('document')) return 'documento';
  return 'foto';
}

export function yearFromDate(value) {
  if (!value) return '';
  const year = String(value).slice(0, 4);
  return /^\d{4}$/.test(year) ? year : '';
}
