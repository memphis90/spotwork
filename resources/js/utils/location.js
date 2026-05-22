const WIDE_LOCATIONS = new Set([
  'italia', 'italy',
  'remote', 'remoto', 'da remoto', 'full remote',
  'mondo', 'world', 'global',
  'europa', 'europe',
  'abruzzo', 'basilicata', 'calabria', 'campania',
  'emilia-romagna', 'emilia romagna',
  'friuli-venezia giulia', 'friuli venezia giulia',
  'lazio', 'liguria', 'lombardia', 'marche', 'molise',
  'piemonte', 'puglia', 'sardegna', 'sicilia',
  'toscana', 'trentino-alto adige', 'trentino alto adige',
  'umbria', "valle d'aosta", 'veneto',
])

export function isWideLocation(city) {
  return WIDE_LOCATIONS.has((city ?? '').trim().toLowerCase())
}
