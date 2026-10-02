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


## API locale

Le backend se trouve dans `api/` et utilise Symfony 8.1 / PHP 8.4.

```bash
cd api
composer install
symfony server:start
```

Le serveur Vite proxifie automatiquement `/api` vers `http://127.0.0.1:8000` en développement.

### Philips Hue

1. Renseigner l'URL locale du bridge dans `api/.env.local`, par exemple `HUE_BRIDGE_URL=https://192.168.1.20`.
2. Appuyer sur le bouton physique du bridge.
3. Dans les 30 secondes, appeler `POST /api/integrations/hue/pair`.
4. Copier la valeur `applicationKey` retournée dans `HUE_APPLICATION_KEY` de `api/.env.local`.
5. `GET /api/lights` retourne alors les lampes Hue réelles.
6. `PUT /api/lights/{id}` avec `{"on":true,"brightness":60}` commande une lampe.

Le certificat local du bridge Hue étant auto-signé, le client HTTP désactive actuellement sa vérification TLS uniquement pour cette connexion locale.
