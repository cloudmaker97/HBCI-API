# HBCI FinTS REST Client

Ein Slim Laravel REST-Client für HBCI FinTS Bankverbindungen mit authentifiziertem API-Zugang.

## Features

- ✅ HBCI FinTS Integration mit nemiah/php-fints
- ✅ Authentifizierter REST-Endpunkt für Kontostand-Abfrage
- ✅ Docker-basierte Entwicklungsumgebung
- ✅ Keine Datenbank erforderlich
- ✅ Konfiguration über Umgebungsvariablen

## Voraussetzungen

- Docker und Docker Compose
- HBCI FinTS Zugangsdaten Ihrer Bank

## Installation

1. **Repository klonen und in das Verzeichnis wechseln:**
   ```bash
   cd hbci-rest-client
   ```

2. **Umgebungsvariablen konfigurieren:**
   ```bash
   cp env.example .env
   ```
   
   Bearbeiten Sie die `.env` Datei mit Ihren FinTS-Zugangsdaten:
   ```env
   # HBCI FinTS Configuration
   FINTS_BANK_URL=https://fints.ihre-bank.de/fints
   FINTS_BANK_CODE=12345678
   FINTS_USERNAME=ihr-username
   FINTS_PIN=ihr-pin
   
   # API Authentication
   API_PASSWORD=ihr-sicheres-api-passwort
   ```

3. **Docker Container starten:**
   ```bash
   docker-compose up --build
   ```

4. **Anwendung testen:**
   ```bash
   curl -H "Authorization: Bearer ihr-sicheres-api-passwort" http://localhost:8000/api/balance
   ```

## API Endpunkte

### GET /api/balance

Ruft den aktuellen Kontostand über HBCI FinTS ab.

**Headers:**
- `Authorization: Bearer {API_PASSWORD}`

**Beispiel-Request:**
```bash
curl -H "Authorization: Bearer mein-api-passwort" \
     http://localhost:8000/api/balance
```

**Beispiel-Response:**
```json
{
  "success": true,
  "balance": 1234.56,
  "currency": "EUR",
  "timestamp": "2024-01-15T10:30:00+01:00"
}
```

**Fehler-Response:**
```json
{
  "error": "Failed to retrieve balance: Connection timeout"
}
```

## Konfiguration

### FinTS-Einstellungen

| Variable | Beschreibung | Beispiel |
|----------|--------------|----------|
| `FINTS_BANK_URL` | FinTS-URL Ihrer Bank | `https://fints.sparkasse.de/fints` |
| `FINTS_BANK_CODE` | Bankleitzahl | `12345678` |
| `FINTS_USERNAME` | Ihr FinTS-Username | `max.mustermann` |
| `FINTS_PIN` | Ihr FinTS-PIN | `123456` |

### API-Sicherheit

| Variable | Beschreibung |
|----------|--------------|
| `API_PASSWORD` | Passwort für API-Zugang |

## Entwicklung

### Lokale Entwicklung

```bash
# Container im Hintergrund starten
docker-compose up -d

# Logs anzeigen
docker-compose logs -f

# Container stoppen
docker-compose down
```

### Code-Struktur

```
hbci-rest-client/
├── public/
│   └── index.php          # Anwendungseinstiegspunkt
├── src/
│   ├── Controllers/
│   │   └── AccountController.php  # FinTS Controller
│   └── Middleware/
│       └── AuthMiddleware.php     # API-Authentifizierung
├── docker-compose.yml     # Docker-Konfiguration
├── Dockerfile            # PHP-Container
├── composer.json         # PHP-Abhängigkeiten
└── .env                  # Umgebungsvariablen
```

## Sicherheitshinweise

- ⚠️ **Nie** FinTS-Zugangsdaten in den Code einbetten
- ⚠️ **Nie** die `.env` Datei committen
- ⚠️ Verwenden Sie ein sicheres API-Passwort
- ⚠️ Beschränken Sie den API-Zugang auf vertrauenswürdige IPs

## Troubleshooting

### Häufige Probleme

1. **FinTS-Verbindung schlägt fehl:**
   - Überprüfen Sie die Bank-URL und Zugangsdaten
   - Stellen Sie sicher, dass FinTS für Ihr Konto aktiviert ist

2. **API-Authentifizierung schlägt fehl:**
   - Überprüfen Sie das API-Passwort in der `.env` Datei
   - Stellen Sie sicher, dass der Authorization-Header korrekt gesetzt ist

3. **Docker-Container startet nicht:**
   - Überprüfen Sie, ob Port 8000 verfügbar ist
   - Führen Sie `docker-compose down` und dann `docker-compose up --build` aus

## Lizenz

MIT License
