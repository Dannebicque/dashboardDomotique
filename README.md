# Dashboard Domotique

Interface tablette Vue 3 + backend Symfony pour consulter et piloter la maison connectée.

## Architecture

En développement :

```text
Vue / Vite :5173
      |
      | /api (proxy Vite)
      v
Symfony :8000
```

En production, le projet est prévu pour tourner sur une machine ARM64 du réseau domestique (Raspberry Pi ou mini-PC) :

```text
Tablette / PWA
      |
      v
Nginx :8080
   |       |
   |       +-- Vue compilé
   |
   +---------- /api -> PHP-FPM / Symfony
                         |
              +----------+----------+
              |                     |
          Hue / TaHoma        APIs Internet
            locales          Spotify / Netatmo
```

Hue et TaHoma restent accessibles localement. Spotify, Netatmo et la météo utilisent l'accès Internet sortant du serveur.

## Développement

Front :

```bash
npm install
npm run dev
```

API :

```bash
cd api
composer install
symfony serve
```

Vite proxifie `/api` vers `http://127.0.0.1:8000`.

Vérifications front :

```bash
npm run typecheck
npm run build
```

## Configuration

Copier les variables nécessaires depuis `api/.env.example` vers `api/.env.local`.

Les secrets applicatifs (client ID / client secret, URLs de callback) restent dans `.env.local`. Les credentials obtenus dynamiquement (refresh tokens Spotify/Netatmo, application key Hue, token TaHoma) sont stockés par Symfony dans `api/var/integrations`.

## Déploiement Raspberry / mini-PC

Docker construit deux images multi-stage compatibles ARM64 :

- `web` : Nginx + build statique Vue ;
- `php` : PHP-FPM 8.4 + Symfony.

Sur le Raspberry :

```bash
git clone <repository>
cd dashboardDomotique
cp api/.env.example api/.env.local
# compléter api/.env.local
docker compose up -d --build
```

Le dashboard est alors disponible sur le port `8080` du Raspberry. Pour changer le port :

```bash
DASHBOARD_PORT=80 docker compose up -d
```

Le volume Docker `integrations` conserve les credentials obtenus par les intégrations entre les rebuilds.

## Intégrations

### Philips Hue

Le backend utilise l'API Hue v2 locale. `HUE_BRIDGE_URL` doit pointer vers le bridge du réseau domestique. L'application key obtenue lors du pairing est persistée par le backend.

### Somfy TaHoma

`TAHOMA_BASE_URL` désigne l'API locale TaHoma. Le mapping des équipements et commandes sera complété à partir du payload réel de l'installation.

### Spotify

Configurer `SPOTIFY_CLIENT_ID`, `SPOTIFY_CLIENT_SECRET` et `SPOTIFY_REDIRECT_URI`. Le backend gère OAuth et persiste le refresh token.

### Netatmo

Configurer `NETATMO_CLIENT_ID`, `NETATMO_CLIENT_SECRET` et `NETATMO_REDIRECT_URI`. Le backend gère OAuth et persiste le refresh token.

## API principale

- `GET /api/dashboard`
- `GET /api/rooms`
- `GET /api/lights`
- `PUT /api/lights/{id}`
- `GET /api/shutters`
- `GET /api/integrations`

Le front consomme toujours des URLs relatives `/api/...`, ce qui permet d'utiliser la même origine sur la tablette et évite une configuration CORS spécifique en production.
