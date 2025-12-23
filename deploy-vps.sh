#!/bin/bash

# 最大ユニーク数ゲーム - VPSデプロイスクリプト
# Ubuntu 22.04/24.04 LTS用

set -e

echo "=================================="
echo "VPSデプロイメントスクリプト"
echo "=================================="

# 色の定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ステップ1: システムアップデート
echo -e "${GREEN}[1/8] システムをアップデート中...${NC}"
sudo apt update
sudo apt upgrade -y

# ステップ2: 必要なパッケージのインストール
echo -e "${GREEN}[2/8] 必要なパッケージをインストール中...${NC}"
sudo apt install -y git curl wget vim ufw

# ステップ3: Dockerのインストール
echo -e "${GREEN}[3/8] Dockerをインストール中...${NC}"
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    rm get-docker.sh
    echo -e "${YELLOW}Dockerをインストールしました。後で再ログインが必要です。${NC}"
else
    echo "Dockerは既にインストールされています。"
fi

# ステップ4: Docker Composeのインストール
echo -e "${GREEN}[4/8] Docker Composeをインストール中...${NC}"
if ! command -v docker-compose &> /dev/null; then
    sudo apt install -y docker-compose
else
    echo "Docker Composeは既にインストールされています。"
fi

# ステップ5: ファイアウォール設定
echo -e "${GREEN}[5/8] ファイアウォールを設定中...${NC}"
sudo ufw --force enable
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
echo "ファイアウォールルール:"
sudo ufw status

# ステップ6: プロジェクトディレクトリの準備
echo -e "${GREEN}[6/8] プロジェクトディレクトリを準備中...${NC}"
PROJECT_DIR="/var/www/unique-number-game"

if [ -d "$PROJECT_DIR" ]; then
    echo -e "${YELLOW}プロジェクトディレクトリが既に存在します。${NC}"
    read -p "削除して再作成しますか？ (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sudo rm -rf "$PROJECT_DIR"
    fi
fi

# ステップ7: Gitリポジトリのクローン
echo -e "${GREEN}[7/8] Gitリポジトリをクローン中...${NC}"
echo -e "${YELLOW}GitHubリポジトリURLを入力してください:${NC}"
read REPO_URL

if [ -n "$REPO_URL" ]; then
    sudo git clone "$REPO_URL" "$PROJECT_DIR"
    sudo chown -R $USER:$USER "$PROJECT_DIR"
    cd "$PROJECT_DIR"
else
    echo -e "${RED}リポジトリURLが指定されていません。手動でプロジェクトをアップロードしてください。${NC}"
    exit 1
fi

# ステップ8: 環境設定
echo -e "${GREEN}[8/8] 環境を設定中...${NC}"

# .env.exampleから.envを作成
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${YELLOW}.envファイルを作成しました。後で編集してください。${NC}"
fi

echo ""
echo -e "${GREEN}=================================="
echo "セットアップ完了！"
echo "==================================${NC}"
echo ""
echo -e "${YELLOW}次のステップ:${NC}"
echo ""
echo "1. .envファイルを編集:"
echo "   nano .env"
echo ""
echo "2. 以下の設定を変更:"
echo "   - APP_ENV=production"
echo "   - APP_DEBUG=false"
echo "   - APP_URL=http://your-domain-or-ip"
echo "   - DB_PASSWORD=secure_password"
echo ""
echo "3. アプリケーションを起動:"
echo "   ./start-app.sh"
echo ""
echo -e "${RED}重要: 新しいシェルを開くか、再ログインしてDockerグループを有効にしてください:${NC}"
echo "   exit"
echo "   ssh user@your-server"
echo ""
