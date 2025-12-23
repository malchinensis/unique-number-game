# デプロイメントガイド

## オプション1: VPS/クラウドサーバーへのデプロイ（推奨）

### 前提条件
- Ubuntu 22.04/24.04 LTS サーバー
- ドメイン名（オプション）
- SSH接続可能

### 1. サーバーの準備

```bash
# サーバーにSSH接続
ssh user@your-server-ip

# システムアップデート
sudo apt update && sudo apt upgrade -y

# Dockerのインストール
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Docker Composeのインストール
sudo apt install docker-compose -y

# 現在のユーザーをdockerグループに追加
sudo usermod -aG docker $USER

# 再ログイン
exit
ssh user@your-server-ip

# Gitのインストール
sudo apt install git -y
```

### 2. プロジェクトのアップロード

#### 方法A: Gitリポジトリを使用（推奨）

```bash
# GitHubなどにプロジェクトをプッシュ
# ローカル（Windowsマシン）で:
cd C:\Users\malch\unique-number-game
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/yourusername/unique-number-game.git
git push -u origin main

# サーバーで:
git clone https://github.com/yourusername/unique-number-game.git
cd unique-number-game
```

#### 方法B: SCPで直接転送

```bash
# ローカル（Windowsマシン）で:
# PowerShellまたはWSLで実行
scp -r C:\Users\malch\unique-number-game user@your-server-ip:/home/user/
```

### 3. 環境設定

```bash
# プロジェクトディレクトリに移動
cd unique-number-game

# .envファイルを編集
nano .env
```

**重要な設定変更:**

```env
APP_NAME="最大ユニーク数ゲーム"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

# 新しいAPP_KEYを生成（後で実行）
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=unique_number_game
DB_USERNAME=sail
DB_PASSWORD=your_secure_password_here  # 変更してください

# セッション設定
SESSION_DRIVER=database
SESSION_LIFETIME=120

WWWGROUP=1000
WWWUSER=1000
```

### 4. アプリケーションキーの生成

```bash
docker-compose up -d mysql redis
docker-compose exec laravel.test php artisan key:generate
```

### 5. コンテナの起動とビルド

```bash
# コンテナをビルドして起動
docker-compose up -d --build

# 依存関係のインストール
docker-compose exec laravel.test composer install --optimize-autoloader --no-dev

# フロントエンドのビルド
docker-compose exec laravel.test npm install
docker-compose exec laravel.test npm run build

# データベースマイグレーション
docker-compose exec laravel.test php artisan migrate --force

# キャッシュの最適化
docker-compose exec laravel.test php artisan config:cache
docker-compose exec laravel.test php artisan route:cache
docker-compose exec laravel.test php artisan view:cache
```

### 6. ファイアウォール設定

```bash
# UFWのインストールと設定
sudo apt install ufw -y
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

### 7. Nginx リバースプロキシ設定（オプション、推奨）

HTTPSとドメイン名を使用する場合:

```bash
# Nginxのインストール
sudo apt install nginx certbot python3-certbot-nginx -y

# Nginx設定ファイル作成
sudo nano /etc/nginx/sites-available/unique-game
```

設定内容:

```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://localhost:80;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
# 設定を有効化
sudo ln -s /etc/nginx/sites-available/unique-game /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# SSL証明書の取得（Let's Encrypt）
sudo certbot --nginx -d your-domain.com
```

### 8. 自動起動設定

```bash
# docker-compose.ymlにrestart: alwaysを追加
nano docker-compose.yml
```

各サービスに追加:

```yaml
services:
  laravel.test:
    restart: always
    # ... 他の設定

  mysql:
    restart: always
    # ... 他の設定

  redis:
    restart: always
    # ... 他の設定
```

```bash
# コンテナを再起動
docker-compose down
docker-compose up -d
```

---

## オプション2: 共有レンタルサーバー（非Docker）

### 前提条件
- PHP 8.3以上
- MySQL 8.0以上
- Composer
- Node.js/npm

### 手順

1. **ローカルでビルド**

```bash
# Windowsマシンで
cd C:\Users\malch\unique-number-game

# 依存関係インストール
composer install --optimize-autoloader --no-dev
npm install
npm run build

# .envファイルを本番用に設定
```

2. **ファイルをアップロード**

FTPクライアント（FileZilla等）で以下をアップロード:
- すべてのプロジェクトファイル
- ただし`node_modules`、`.git`は除外

3. **データベース設定**

レンタルサーバーの管理画面で:
- MySQLデータベースを作成
- ユーザーを作成
- `.env`ファイルを更新

4. **公開ディレクトリ設定**

Webサーバーのドキュメントルートを`public`ディレクトリに設定

5. **パーミッション設定**

```bash
chmod -R 755 storage bootstrap/cache
```

---

## オプション3: Herokuへのデプロイ

### 前提条件
- Herokuアカウント
- Heroku CLI

### 手順

```bash
# Heroku CLIのインストール
# https://devcenter.heroku.com/articles/heroku-cli

# ログイン
heroku login

# アプリケーション作成
heroku create your-app-name

# PostgreSQLアドオン追加
heroku addons:create heroku-postgresql:essential-0

# Redisアドオン追加
heroku addons:create heroku-redis:mini

# 環境変数設定
heroku config:set APP_KEY=$(php artisan key:generate --show)
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false

# Procfileを作成
echo "web: vendor/bin/heroku-php-apache2 public/" > Procfile

# デプロイ
git add .
git commit -m "Deploy to Heroku"
git push heroku main

# マイグレーション実行
heroku run php artisan migrate --force
```

---

## オプション4: Laravel Forge（有料、最も簡単）

Laravel Forgeは自動デプロイメントサービスです:
https://forge.laravel.com

1. Forgeアカウント作成
2. サーバーをプロビジョニング
3. Gitリポジトリを接続
4. 自動デプロイ設定

---

## セキュリティチェックリスト

デプロイ前に必ず確認:

- [ ] `APP_DEBUG=false`に設定
- [ ] `APP_ENV=production`に設定
- [ ] データベースパスワードを変更
- [ ] `APP_KEY`を新規生成
- [ ] `.env`ファイルをGitにコミットしない
- [ ] HTTPS/SSL証明書を設定
- [ ] ファイアウォール設定
- [ ] 定期バックアップ設定
- [ ] ログ監視設定

---

## メンテナンスコマンド

### ログ確認

```bash
docker-compose logs -f laravel.test
```

### データベースバックアップ

```bash
docker-compose exec mysql mysqldump -u sail -p unique_number_game > backup.sql
```

### アプリケーション更新

```bash
git pull origin main
docker-compose exec laravel.test composer install --no-dev
docker-compose exec laravel.test npm run build
docker-compose exec laravel.test php artisan migrate --force
docker-compose exec laravel.test php artisan config:cache
docker-compose exec laravel.test php artisan route:cache
docker-compose exec laravel.test php artisan view:cache
docker-compose restart laravel.test
```

---

## トラブルシューティング

### パーミッションエラー

```bash
docker-compose exec laravel.test chmod -R 775 storage bootstrap/cache
docker-compose exec laravel.test chown -R www-data:www-data storage bootstrap/cache
```

### データベース接続エラー

```bash
# MySQLコンテナの再起動
docker-compose restart mysql

# 接続確認
docker-compose exec laravel.test php artisan db:show
```

### キャッシュクリア

```bash
docker-compose exec laravel.test php artisan cache:clear
docker-compose exec laravel.test php artisan config:clear
docker-compose exec laravel.test php artisan route:clear
docker-compose exec laravel.test php artisan view:clear
```
