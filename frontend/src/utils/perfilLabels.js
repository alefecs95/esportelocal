export const PERFIL_LABELS = {
  ADMIN: 'Administrador',
  SUPER_ADMIN: 'Super administrador',
  REDATOR: 'Redator',
  ARBITRO: 'Árbitro',
  RESPONSAVEL: 'Responsável do clube',
  ATLETA: 'Atleta',
};

export const perfilLabel = (perfil) => PERFIL_LABELS[perfil] || perfil || '-';
