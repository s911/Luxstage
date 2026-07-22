# Luxstage 离线部署制品清单

适用于**无法访问外网**的生产/测试服务器。所有依赖需在有网络的 PC 上提前下载，再拷贝到服务器本地安装。

---

## 一、流程总览

### 在有网络的 PC（本地开发机）

**Windows（推荐，仅下载 ZIP，不下载 Docker 镜像）：**

```powershell
powershell -ExecutionPolicy Bypass -File deploy/offline/prepare-bundle.ps1
```

**Linux / Mac：**

```bash
bash deploy/offline/prepare-bundle.sh
# 需要同时打包 Docker 镜像时：
# bash deploy/offline/prepare-bundle.sh --include-docker
```

将**整个项目目录**（含 `deploy/offline/packages/`）拷贝到离线服务器（U盘 / 内网 SCP / 堡垒机）。

> Docker 镜像默认不在 Windows 上下载。若服务器上尚无镜像，请在**有 Docker 的机器**上单独准备（见下文），或部署时加 `--skip-image-load`（镜像已存在时）。

### 在离线服务器

```bash
# 1. 手动安装 Docker Engine + Compose 插件（见下文）
# 2. 一键部署
cd /opt/luxstage
sudo bash deploy/one-click-deploy-offline.sh \
  --domain 192.168.1.100 \
  --email admin@luxstage.com \
  --seed-demo-data
```

---

## 二、必须手动准备的制品

### 1. Docker 镜像（`deploy/offline/images/`，或在服务器上直接 `docker pull`）

| 文件名 | 来源 | 用途 |
|--------|------|------|
| `wordpress.tar` | `docker pull wordpress:6.6.2-php8.3-apache && docker save` | WordPress 运行容器 |
| `mysql-8.0.tar` | `docker pull mysql:8.0 && docker save` | MySQL 数据库 |
| `wordpress-cli.tar` | `docker pull wordpress:cli && docker save` | WP-CLI 管理工具 |

> `prepare-bundle.ps1` / `prepare-bundle.sh` **默认不下载** Docker 镜像。在装有 Docker 的机器上执行 `prepare-bundle.sh --include-docker`，或在部署服务器上手动 `docker pull` 后使用 `--skip-image-load`。

### 2. WordPress 与插件 ZIP（`deploy/offline/packages/`）

| 文件名 | 下载地址 | 是否必须 |
|--------|----------|----------|
| `wordpress-6.6.2.zip` | https://wordpress.org/wordpress-6.6.2.zip | **必须** |
| `advanced-custom-fields.zip` | https://downloads.wordpress.org/plugin/advanced-custom-fields.latest-stable.zip | **必须** |
| `contact-form-7.zip` | https://downloads.wordpress.org/plugin/contact-form-7.6.1.6.zip | **必须** |
| `polylang.zip` | https://downloads.wordpress.org/plugin/polylang.latest-stable.zip | **必须** |
| `seo-by-rank-math.zip` | https://downloads.wordpress.org/plugin/seo-by-rank-math.latest-stable.zip | **必须** |
| `fluentform.zip` | https://downloads.wordpress.org/plugin/fluentform.latest-stable.zip | 可选 |
| `elementor.zip` | https://downloads.wordpress.org/plugin/elementor.latest-stable.zip | 可选 |
| `elementor-pro.zip` | 商业授权包，自行放置 | 可选 |
| `webp-converter-for-media.zip` | https://downloads.wordpress.org/plugin/webp-converter-for-media.latest-stable.zip | 可选 |

> 项目根目录已有 `contact-form-7.6.1.6.zip` 时，`prepare-bundle.sh` 会自动复制为 `contact-form-7.zip`。

### 3. 宿主机系统依赖（需在服务器本机安装，无法用脚本在线安装）

| 组件 | 说明 |
|------|------|
| **Docker Engine 24+** | 离线 RPM/DEB 包安装，或内网镜像源 |
| **Docker Compose 插件 2.20+** | `docker compose version` 可用 |
| **unzip** | 解压 WordPress 核心 |
| **curl** | 部署后健康检查（仅访问本机） |
| **nginx**（可选） | 仅在使用 `--with-nginx` 时需要 |

#### Docker 离线安装参考

在有网机器下载（按服务器 OS 选择）：

- CentOS/Rocky/Alma: https://download.docker.com/linux/centos/
- Ubuntu/Debian: https://download.docker.com/linux/ubuntu/

需要包（示例）：
- `containerd.io`
- `docker-ce`
- `docker-ce-cli`
- `docker-compose-plugin`

拷贝到服务器后：

```bash
sudo dnf install -y ./containerd.io-*.rpm ./docker-ce-*.rpm ./docker-ce-cli-*.rpm ./docker-compose-plugin-*.rpm
# 或 Ubuntu: sudo dpkg -i *.deb
sudo systemctl enable --now docker
```

---

## 三、目录结构（部署前检查）

```
Luxstage/
├── deploy/
│   ├── one-click-deploy-offline.sh    # 离线一键部署入口
│   └── offline/
│       ├── ARTIFACTS.md               # 本文件
│       ├── prepare-bundle.sh          # 有网 PC 打包脚本
│       ├── load-images.sh             # 加载 Docker 镜像
│       ├── images/
│       │   ├── wordpress.tar          # 必须
│       │   ├── mysql-8.0.tar          # 必须
│       │   └── wordpress-cli.tar      # 必须
│       └── packages/
│           ├── wordpress-6.6.2.zip    # 必须
│           ├── advanced-custom-fields.zip
│           ├── contact-form-7.zip
│           ├── polylang.zip
│           └── seo-by-rank-math.zip
├── src/
│   ├── .htaccess                      # Git 已跟踪
│   └── wp-content/                    # 主题与 mu-plugin
└── docker-compose.prod.yml
```

---

## 四、一键部署命令

```bash
sudo bash deploy/one-click-deploy-offline.sh \
  --domain YOUR_SERVER_IP_OR_DOMAIN \
  --email admin@example.com \
  --seed-demo-data
```

### 常用参数

| 参数 | 说明 |
|------|------|
| `--domain` | 站点域名或 IP，写入 `WP_URL` |
| `--email` | WordPress 管理员邮箱 |
| `--with-nginx` | 配置 Nginx 80 端口反代（HTTP，离线环境无法自动申请 SSL） |
| `--seed-demo-data` | 导入演示产品/目录/案例/询盘数据 |
| `--skip-image-load` | 镜像已加载时跳过 `docker load` |

### 部署后验证

```bash
curl -I http://127.0.0.1:8080/
curl -I http://127.0.0.1:8080/wp-json/
curl -I http://127.0.0.1:8080/products/
```

凭据保存在：`deploy-credentials.txt`（勿提交 Git）。

---

## 五、重要说明

1. **Git 不包含 WordPress 核心文件**（`wp-admin/`、`wp-includes/` 等），部署脚本会从 `wordpress-6.6.2.zip` 自动解压。
2. **不要在生产服务器上执行 `git reset --hard`**，会删除未纳入 Git 的 WordPress 核心文件。
3. 离线环境**无法自动申请 Let's Encrypt 证书**；HTTPS 需手动导入证书并配置 Nginx。
4. SMTP 邮件需在 `.env` 中配置内网邮件服务器，或部署后编辑 `.env` 重启容器。

---

## 六、故障排查

| 现象 | 处理 |
|------|------|
| `MISSING: deploy/offline/...` | 在有网 PC 重新运行 `prepare-bundle.sh` 并拷贝制品 |
| `wp-blog-header.php` 缺失 / HTTP 500 | `bash deploy/scripts/extract-wordpress-core.sh` |
| `/products/` 404 | `bash deploy/scripts/ensure-htaccess.sh` 后 `wp rewrite flush --hard` |
| `docker compose` 不存在 | 手动安装 compose 插件 |
| 插件安装失败 | 确认对应 zip 在 `deploy/offline/packages/` |
