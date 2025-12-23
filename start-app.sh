#!/bin/bash

# アプリケーション起動スクリプト

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=================================="
echo "アプリケーションを起動中..."
echo "==================================${NC}"

# ステップ1: Dockerコンテナのビルドと起動
echo -e "${GREEN}[1/7] Dockerコンテナをビルド中...${NC}"
docker-compose up -d --build

# 少し待つ
echo "コンテナの起動を待っています..."
sleep 10

# ステップ2: アプリケーションキーの生成
echo -e "${GREEN}[2/7] アプリケーションキーを生成中...${NC}"
if grep -q "APP_KEY=base64:" .env; then
    echo "APP_KEYは既に設定されています。"
else
    docker-compose exec -T laravel.test php artisan key:generate
fi

# ステップ3: Composer依存関係のインストール
echo -e "${GREEN}[3/7] Composer依存関係をインストール中...${NC}"
docker-compose exec -T laravel.test composer install --optimize-autoloader --no-dev

# ステップ4: NPM依存関係のインストールとビルド
echo -e "${GREEN}[4/7] フロントエンドをビルド中...${NC}"
docker-compose exec -T laravel.test npm install --production=false
docker-compose exec -T laravel.test npm run build

# ステップ5: データベースマイグレーション
echo -e "${GREEN}[5/7] データベースマイグレーションを実行中...${NC}"
docker-compose exec -T laravel.test php artisan migrate --force

# ステップ6: パーミッション設定
echo -e "${GREEN}[6/7] パーミッションを設定中...${NC}"
docker-compose exec -T laravel.test chmod -R 775 storage bootstrap/cache
docker-compose exec -T laravel.test chown -R www-data:www-data storage bootstrap/cache

# ステップ7: キャッシュの最適化
echo -e "${GREEN}[7/7] キャッシュを最適化中...${NC}"
docker-compose exec -T laravel.test php artisan config:cache
docker-compose exec -T laravel.test php artisan route:cache
docker-compose exec -T laravel.test php artisan view:cache

echo ""
echo -e "${GREEN}=================================="
echo "起動完了！"
echo "==================================${NC}"
echo ""
echo "コンテナの状態:"
docker-compose ps
echo ""
echo -e "${YELLOW}アプリケーションにアクセス:${NC}"
echo "http://$(curl -s ifconfig.me)"
echo ""
echo -e "${YELLOW}便利なコマンド:${NC}"
echo "  ログ確認:       docker-compose logs -f laravel.test"
echo "  コンテナ停止:   docker-compose down"
echo "  コンテナ再起動: docker-compose restart"
echo ""
