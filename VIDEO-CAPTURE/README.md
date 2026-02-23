# Gästebuch PoC (Docker, LAMP)

Minimaler, sofort lauffähiger Gästebuch-Prototyp mit:

- PHP 8.3 + Apache (eigenes Dockerfile inkl. `pdo_mysql`)
- MySQL 8.4
- phpMyAdmin 5

## Starten

```bash
cd VIDEO-CAPTURE
docker compose up --build -d
```

## Öffnen im Browser

- Gästebuch: http://localhost:8080
- phpMyAdmin: http://localhost:8081

## phpMyAdmin Login

- Server: `db`
- Benutzer: `root`
- Passwort: `rootsecret`

(Alternativ App-User: `app` / `secret123`)
