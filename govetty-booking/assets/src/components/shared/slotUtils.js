export function parseSlotDate(dateStr) {
  const value = String(dateStr).trim();
  const ymd = value.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
  const dmy = value.match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
  const match = ymd || dmy;
  if (!match) return new Date(Number.NaN);

  const [, first, second, third] = match;
  const year = Number(ymd ? first : third);
  const month = Number(second);
  const day = Number(ymd ? third : first);
  const date = new Date(year, month - 1, day);
  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
    return new Date(Number.NaN);
  }
  return date;
}

export function formatSlotDate(dateStr, options = { weekday: 'short', month: 'short', day: 'numeric' }) {
  const date = parseSlotDate(dateStr);
  return Number.isNaN(date.getTime()) ? String(dateStr) : date.toLocaleDateString(undefined, options);
}

export function getSlotTimestamp(slot) {
  const date = parseSlotDate(slot.date || '');
  const time = String(slot.time_start || '').match(/^(\d{1,2}):(\d{2})/);
  if (Number.isNaN(date.getTime())) return Number.MAX_SAFE_INTEGER;
  if (time) date.setHours(Number(time[1]), Number(time[2]));
  return date.getTime();
}
