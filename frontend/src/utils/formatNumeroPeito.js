// Número de peito sempre com 3 dígitos para exibição (ex.: 1 -> "001", 42 -> "042", 1000 -> "1000").
export const formatNumeroPeito = (value) => {
  if (value === null || value === undefined || value === '') return '';
  const numero = Math.trunc(Number(value));
  if (!Number.isFinite(numero)) return String(value);
  return String(numero).padStart(3, '0');
};
