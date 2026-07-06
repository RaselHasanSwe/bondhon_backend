# Bondhon Backend Deployment Guide

> Production deployment notes for Ubuntu 24.04 LTS (DigitalOcean)

---

# Server Information

| Component | Version | Status |
|-----------|----------|--------|
| Ubuntu | 24.04 LTS | ✅ |
| PHP | 8.3.6 | ✅ |
| Composer | 2.10.2 | ✅ |
| MySQL | 8.0.46 | ✅ |
| Redis | 7.0.15 | ✅ |
| Nginx | 1.24 | ✅ |
| Node.js | 24.18.0 | ✅ |
| npm | 11.16.0 | ✅ |
| Supervisor | Installed | ✅ |

---

# Installed Services

```text
nginx
php8.3-fpm
mysql
redis-server
supervisor
imagemagick
ffmpeg
coturn
```

---

# Project Location

```text
/srv/bondhon_backend
```

Project owner:

```text
rasel
```

Web server user:

```text
www-data
```

---

# Nginx

Configuration

```text
/etc/nginx/sites-available/bondhon_backend
```

Backend listens on

```text
http://127.0.0.1:9001
```

Nginx reverse proxies requests to Laravel.

Useful commands

```bash
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl restart nginx
```

---

# PHP

Restart PHP

```bash
sudo systemctl restart php8.3-fpm
```

Check version

```bash
php -v
```

---

# Laravel Commands

Clear cache

```bash
php artisan optimize:clear
```

Optimize

```bash
php artisan optimize
```

Cache config

```bash
php artisan config:cache
```

Cache routes

```bash
php artisan route:cache
```

Run migration

```bash
php artisan migrate --force
```

Restart queue workers after deployment

```bash
php artisan queue:restart
```

---

# File Permissions

If Laravel cannot write logs/cache:

```bash
sudo chown -R rasel:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

# Queue Worker

Supervisor configuration

```text
/etc/supervisor/conf.d/mybouma.conf
```

Commands

```bash
sudo supervisorctl reread
sudo supervisorctl update

sudo supervisorctl start mybouma:*
sudo supervisorctl restart mybouma:*
sudo supervisorctl status
```

After every deployment

```bash
php artisan queue:restart
```

---

# Laravel Reverb

Supervisor configuration

```text
/etc/supervisor/conf.d/reverb.conf
```

Commands

```bash
sudo supervisorctl reread
sudo supervisorctl update

sudo supervisorctl start reverb
sudo supervisorctl restart reverb
sudo supervisorctl status
```

Run manually

```bash
php artisan reverb:start
```

---

# Redis

Restart

```bash
sudo systemctl restart redis-server
```

Status

```bash
sudo systemctl status redis-server
```

---

# MySQL

Restart

```bash
sudo systemctl restart mysql
```

Login

```bash
mysql -u root -p
```

---

# Coturn (TURN Server)

Installation

```bash
sudo apt install coturn -y
```

Version

```bash
turnserver --version
```

Enable

```bash
sudo nano /etc/default/coturn
```

```text
TURNSERVER_ENABLED=1
```

Configuration

```text
/etc/turnserver.conf
```

Restart

```bash
sudo systemctl restart coturn
```

Enable on boot

```bash
sudo systemctl enable coturn
```

Status

```bash
sudo systemctl status coturn
```

---

## Current Testing Configuration

Using static username/password authentication.

```ini
listening-port=3478
external-ip=206.189.87.32

lt-cred-mech

user=test:test123

realm=206.189.87.32

fingerprint
```

TLS is currently disabled until a domain and SSL certificate are available.

---

# Firewall Ports

Allow TURN traffic

```bash
sudo ufw allow 3478/tcp
sudo ufw allow 3478/udp

sudo ufw allow 5349/tcp
sudo ufw allow 5349/udp

sudo ufw allow 49152:65535/udp
```

---

# Deployment Checklist

```bash
git pull

composer install --no-dev --optimize-autoloader

php artisan migrate --force

php artisan optimize

php artisan queue:restart

sudo supervisorctl restart reverb

sudo systemctl restart php8.3-fpm
```

---

# Useful Commands

Check running services

```bash
systemctl status nginx
systemctl status php8.3-fpm
systemctl status mysql
systemctl status redis-server
systemctl status coturn
```

Supervisor

```bash
sudo supervisorctl status
```

Logs

Laravel

```bash
tail -f storage/logs/laravel.log
```

Nginx

```bash
sudo tail -f /var/log/nginx/error.log
```

PHP

```bash
sudo journalctl -u php8.3-fpm -f
```

Reverb

```bash
tail -f /var/log/reverb.log
```

Queue

```bash
tail -f /var/log/laravel-worker.log
```

---

# Server Architecture

```
Internet
        │
        ▼
      Nginx (:80/:443)
        │
 ┌──────┴─────────┐
 │                │
 ▼                ▼
Laravel       Next.js
PHP-FPM        PM2
 │
 ▼
MySQL
Redis
 │
 ▼
Queue Workers (Supervisor)

Reverb (Supervisor)
        │
        ▼
WebSocket

Coturn
UDP/TCP :3478
```

---

# Important Notes

- Backend runs internally on **127.0.0.1:9001**.
- Nginx is the public entry point.
- Reverb is managed by Supervisor.
- Queue workers are managed by Supervisor.
- Coturn currently uses **username/password authentication** for testing.
- After obtaining a domain, switch Coturn to **TLS (5349)** with a Let's Encrypt certificate.
- After every deployment:
  - Pull latest code
  - Install Composer dependencies (if changed)
  - Run migrations
  - Optimize Laravel
  - Restart queue workers (`php artisan queue:restart`)
  - Restart Reverb if its code/config changed
  - Restart PHP-FPM if PHP configuration changed
