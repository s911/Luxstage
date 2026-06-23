# Luxstage Production Deployment Guide (CentOS/Rocky/Alma)

## 1) Prepare host

```bash
sudo dnf -y update
sudo dnf -y install git curl
```

Install Docker Engine and Compose plugin according to your distro standard.

## 2) Deploy project

```bash
sudo mkdir -p /opt/luxstage
sudo chown -R $USER:$USER /opt/luxstage
cd /opt/luxstage
git clone <your-repo-url> .
cp .env.example .env
```

Edit `.env` with production credentials.

## 3) Start app stack

```bash
docker compose up -d --build
docker compose exec web bash -lc "bash /usr/local/bin/install-plugins.sh"
docker compose exec web wp theme activate fabricwarm-b2b --path=/var/www/html
docker compose exec web wp rewrite structure '/%postname%/' --hard --path=/var/www/html
docker compose exec web wp rewrite flush --hard --path=/var/www/html
```

## 4) Configure Nginx reverse proxy + SSL

Set domain in `deploy/nginx/luxstage.conf` if needed.

```bash
sudo bash deploy/scripts/install-nginx.sh
sudo bash deploy/scripts/setup-ssl.sh luxstage.com admin@luxstage.com
```

## 5) Enable auto-start

```bash
sudo bash deploy/scripts/install-systemd.sh /opt/luxstage
```

## 6) Backup & rollback

Manual backup:

```bash
PROJECT_ROOT=/opt/luxstage bash deploy/scripts/backup.sh
```

Rollback latest snapshot:

```bash
PROJECT_ROOT=/opt/luxstage bash deploy/scripts/rollback.sh
```

Install daily backup cron (02:30):

```bash
sudo bash deploy/scripts/install-backup-cron.sh /opt/luxstage "30 2 * * *"
```
