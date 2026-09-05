export const digitsOnlyCpf = (value) => String(value || '').replace(/\D/g, '').slice(0, 11);

export const formatCpf = (value) => {
  const digits = digitsOnlyCpf(value);
  if (!digits) return '';
  if (digits.length <= 3) return digits;
  if (digits.length <= 6) return digits.replace(/(\d{3})(\d+)/, '$1.$2');
  if (digits.length <= 9) return digits.replace(/(\d{3})(\d{3})(\d+)/, '$1.$2.$3');
  return digits.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
};

export const formatCpfDisplay = (value) => {
  const digits = digitsOnlyCpf(value);
  if (digits.length !== 11) return value || '-';
  return formatCpf(digits);
};
