# Luxstage Production Deployment Guide

This project can be deployed from a fresh Linux server with one script.

## Recommended Server

- OS: Rocky Linux 9 / AlmaLinux 9 / Ubuntu 22.04 LTS or newer
- CPU: 2 vCPU minimum, 4 vCPU recommended
- Memory: 4 GB minimum, 8 GB recommended
- Disk: 60 GB SSD minimum, 100 GB+ recommended
- Network: public IPv4, ports 80 and 443 open
- Domain: an A record for `example.com` and optional `www.example.com` pointing to the server IP

## One-Click Deploy

After purchasing the server and pointing DNS to it:

```bash
sudo mkdir -p /opt/luxstage
sudo chown -R "$USER:$USER" /opt/luxstage
cd /opt/luxstage
git clone <your-github-repo-url> .
sudo bash deploy/one-click-deploy.sh --domain luxstage.com --email admin@luxstage.com
```

The script installs:

- Git, curl, OpenSSL
- Docker Engine and Docker Compose plugin
- Nginx
- Certbot and Let's Encrypt SSL
- WordPress 6.6.2 Docker stack
- MySQL 8.0
- Required WordPress plugins
- Luxstage theme activation
- Permalinks and production site URL
- systemd auto-start service

Generated credentials are saved on the server:

```bash
/opt/luxstage/deploy-credentials.txt
```

Do not commit `.env` or `deploy-credentials.txt` to GitHub.

## Optional Deploy Flags

```bash
sudo bash deploy/one-click-deploy.sh \
  --domain luxstage.com \
  --email admin@luxstage.com \
  --admin-user luxstage_admin \
  --title "Luxstage B2B"
```

For HTTP-only test deployment:

```bash
sudo bash deploy/one-click-deploy.sh --domain your-server-ip-or-domain --email admin@example.com --no-ssl
```

For demo data:

```bash
sudo bash deploy/one-click-deploy.sh --domain luxstage.com --email admin@luxstage.com --seed-demo-data
```

## SMTP and WhatsApp

Before running the script, you can pass SMTP and WhatsApp values as environment variables:

```bash
sudo LUXSTAGE_SMTP_HOST=smtp.example.com \
  LUXSTAGE_SMTP_USER=no-reply@luxstage.com \
  LUXSTAGE_SMTP_PASSWORD='your-password' \
  LUXSTAGE_WHATSAPP_NUMBER='+8613800000000' \
  bash deploy/one-click-deploy.sh --domain luxstage.com --email admin@luxstage.com
```

## Backup & Rollback

Manual backup:

```bash
PROJECT_ROOT=/opt/luxstage bash /opt/luxstage/deploy/scripts/backup.sh
```

Rollback latest snapshot:

```bash
PROJECT_ROOT=/opt/luxstage bash /opt/luxstage/deploy/scripts/rollback.sh
```

Install daily backup cron (02:30):

```bash
sudo bash deploy/scripts/install-backup-cron.sh /opt/luxstage "30 2 * * *"
```

## Offline / Air-Gapped Deploy

For servers **without internet access**, use the offline workflow.

### Step 1: On a PC with internet

```bash
cd Luxstage
bash deploy/offline/prepare-bundle.sh
git push   # push code + scripts; large tar/zip files stay local and are copied manually
```

Copy the full project folder to the offline server, including:

- `deploy/offline/images/*.tar`
- `deploy/offline/packages/*.zip`

See the full checklist: [deploy/offline/ARTIFACTS.md](offline/ARTIFACTS.md)

### Step 2: On the offline server

Install Docker Engine + Compose plugin manually, then run:

```bash
cd /opt/luxstage
sudo bash deploy/one-click-deploy-offline.sh \
  --domain 192.168.1.100 \
  --email admin@luxstage.com \
  --seed-demo-data
```

Optional Nginx HTTP reverse proxy:

```bash
sudo bash deploy/one-click-deploy-offline.sh \
  --domain luxstage.internal \
  --email admin@luxstage.com \
  --with-nginx \
  --seed-demo-data
```

### Offline scripts

| Script | Purpose |
|--------|---------|
| `deploy/offline/prepare-bundle.sh` | Download images/plugins on connected PC |
| `deploy/offline/load-images.sh` | `docker load` on offline server |
| `deploy/one-click-deploy-offline.sh` | Full offline deployment |
| `deploy/scripts/extract-wordpress-core.sh` | Restore WP core from local zip |
| `deploy/scripts/install-plugins-offline.sh` | Install plugins from local zip |
| `deploy/scripts/bootstrap-wordpress-site.sh` | Pages, CF7 forms, theme, permalinks |
