<p align="center">
  <img src="resources/images/sw_full.png" width="260" alt="Spotwork" />
</p>

<h3 align="center">Scopri quali aziende assumono intorno a te.</h3>

<p align="center">
  <a href="https://spotwork.arabel.dev">spotwork.arabel.dev</a> ·
  <a href="#come-funziona">Come funziona</a> ·
  <a href="#installazione">Installazione</a> ·
  <a href="#contribuire">Contribuire</a>
</p>

---

**Spotwork** è una mappa interattiva open source che incrocia i dati geografici di [OpenStreetMap](https://www.openstreetmap.org) con gli annunci di lavoro attivi su Indeed, LinkedIn e altri job board — mostrandoti in un colpo d'occhio quali aziende vicino a te stanno assumendo adesso.

## Come funziona

1. **Cerca una città** e scegli il raggio (2–50 km) e la categoria merceologica.
2. **Overpass API** recupera le aziende presenti su OpenStreetMap nell'area selezionata.
3. **Adzuna** e **SerpAPI Google Jobs** cercano gli annunci attivi nella stessa area.
4. I risultati vengono incrociati per nome: le aziende OSM che matchano un annuncio vengono evidenziate come *"Assume"*; le aziende presenti solo sui job board vengono aggiunte alla mappa come pin separati.
5. Cliccando su un'azienda puoi vedere i dettagli, il numero di annunci attivi, un link diretto all'offerta e inviare una candidatura spontanea via email.

## Stack

| Layer | Tecnologia |
|---|---|
| Backend | Laravel 11, PHP 8.3 |
| Frontend | Vue 3, Inertia.js |
| Mappa | Leaflet + tile CartoDB Voyager |
| Dati geo | OpenStreetMap / Overpass API |
| Dati lavoro | Adzuna API, SerpAPI Google Jobs |
| Cache | Redis (geocoding, Overpass, job search) |
| Mobile | NativePHP (Android) |

## Installazione

```bash
git clone https://github.com/memphis90/spotwork
cd spotwork

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configura le variabili nel `.env`:

```env
SERPAPI_KEY=...
ADZUNA_APP_ID=...
ADZUNA_APP_KEY=...
```

```bash
php artisan migrate
npm run build
composer run dev
```

## Contribuire

Il progetto è **open source** e accetta contributi di ogni tipo:

- 🗺 **Dati OSM** — se conosci un'azienda non ancora mappata, [aggiungila su OpenStreetMap](https://www.openstreetmap.org).
- 🐛 **Bug report** — apri una [issue su GitHub](https://github.com/memphis90/spotwork/issues).
- 💡 **Feature request** — proponi nuove funzionalità nelle discussioni.
- 🔧 **Pull request** — fork, branch, PR. Vedi [CLAUDE.md](CLAUDE.md) per le convenzioni del progetto.

## Licenza

MIT — vedi [LICENSE](LICENSE).
