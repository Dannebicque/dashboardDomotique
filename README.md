# Dashboard Domotique

MVP d'interface tablette pour consulter et piloter une maison connectée.

## MVP

- dashboard 9–10 pouces en mode paysage ;
- données Netatmo simulées ;
- météo simulée ;
- lecture Spotify simulée ;
- état des lumières et volets ;
- page de contrôle par pièce ;
- interactions locales Hue/Somfy simulées.

## Stack

Vue 3, TypeScript, Vite, Vue Router et Lucide.

## Démarrage

```bash
npm install
npm run dev
```

Vérification :

```bash
npm run typecheck
npm run build
```

## Architecture cible

Le front ne dialoguera pas directement avec chaque fournisseur. Un backend Symfony servira d'agrégateur et exposera un modèle métier indépendant des marques.

```text
Tablette Vue
    |
Symfony / API
    |-- Philips Hue
    |-- Somfy TaHoma
    |-- Netatmo
    |-- Spotify
    `-- météo
```

Les données simulées sont centralisées dans `src/data/mock.ts` afin de pouvoir les remplacer progressivement par l'API.

## Suite

1. PWA / mode kiosque.
2. Backend Symfony.
3. Adapter Philips Hue.
4. Adapter Somfy TaHoma.
5. Netatmo.
6. Spotify.
7. météo.
8. synchronisation temps réel SSE/Mercure.
9. scènes domotiques.
