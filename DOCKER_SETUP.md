# 最大ユニーク数ゲーム - Docker実行ガイド

## 前提条件
- Docker Desktop for Windowsがインストールされていること
- Docker Desktopが起動していること

## セットアップ手順

### 1. プロジェクトディレクトリに移動
```bash
cd C:\Users\malch\unique-number-game
```

### 2. Sailエイリアスの設定（PowerShellの場合）
```powershell
# PowerShellで以下のコマンドを実行
function sail {
    docker-compose exec laravel.test php artisan @args
}
```

または、毎回以下のように実行できます:
```bash
docker-compose exec laravel.test php artisan [コマンド]
```

### 3. Dockerコンテナを起動
```bash
# 初回起動（イメージをビルド）
docker-compose up -d --build

# 2回目以降
docker-compose up -d
```

**注意**: 初回起動は10-15分程度かかる場合があります（イメージのダウンロードとビルド）。

### 4. コンテナの状態確認
```bash
docker-compose ps
```

以下のようなサービスが起動していればOK:
- laravel.test (アプリケーション)
- mysql (データベース)
- redis (キャッシュ)

### 5. 依存関係のインストール
```bash
# Composerパッケージのインストール
docker-compose exec laravel.test composer install

# NPMパッケージのインストール（フロントエンド用）
docker-compose exec laravel.test npm install
```

### 6. データベースのマイグレーション
```bash
docker-compose exec laravel.test php artisan migrate
```

### 7. フロントエンドのビルド
```bash
# 開発用（1回だけビルド）
docker-compose exec laravel.test npm run build

# または開発用（ファイル変更を監視）
docker-compose exec laravel.test npm run dev
```

## アプリケーションへのアクセス

ブラウザで以下のURLにアクセス:
```
http://localhost
```

## よく使うコマンド

### コンテナの起動
```bash
docker-compose up -d
```

### コンテナの停止
```bash
docker-compose down
```

### ログの確認
```bash
# 全サービスのログ
docker-compose logs -f

# 特定のサービスのログ
docker-compose logs -f laravel.test
```

### Artisanコマンドの実行
```bash
docker-compose exec laravel.test php artisan [コマンド]

# 例:
docker-compose exec laravel.test php artisan migrate
docker-compose exec laravel.test php artisan make:controller TestController
docker-compose exec laravel.test php artisan tinker
```

### データベースにアクセス
```bash
docker-compose exec mysql mysql -u sail -p
# パスワード: password
```

### コンテナ内でbashを起動
```bash
docker-compose exec laravel.test bash
```

## トラブルシューティング

### ポート80が既に使用されている場合
`.env`ファイルに以下を追加:
```env
APP_PORT=8080
```

その後、`http://localhost:8080`でアクセス

### データベース接続エラーの場合
```bash
# MySQLコンテナの再起動
docker-compose restart mysql

# 全コンテナの再起動
docker-compose restart
```

### キャッシュのクリア
```bash
docker-compose exec laravel.test php artisan cache:clear
docker-compose exec laravel.test php artisan config:clear
docker-compose exec laravel.test php artisan route:clear
docker-compose exec laravel.test php artisan view:clear
```

### 完全なリセット
```bash
# コンテナとボリュームを削除
docker-compose down -v

# 再度起動
docker-compose up -d --build
docker-compose exec laravel.test php artisan migrate
```

## 開発ワークフロー

1. **Dockerコンテナを起動**
   ```bash
   docker-compose up -d
   ```

2. **コードを編集**
   - VSCodeなどで通常通りファイルを編集
   - Dockerボリュームマウントにより、変更は即座に反映されます

3. **マイグレーションやシーダーの実行**
   ```bash
   docker-compose exec laravel.test php artisan migrate
   docker-compose exec laravel.test php artisan db:seed
   ```

4. **NPMのwatch起動（フロントエンド開発時）**
   ```bash
   docker-compose exec laravel.test npm run dev
   ```

5. **開発終了時**
   ```bash
   docker-compose down
   ```

## 次のステップ

1. ブラウザで `http://localhost` にアクセス
2. ユーザー登録
3. ゲームを作成してプレイ開始！

## パフォーマンス向上のヒント

### Windows上でのDocker性能改善
1. **WSL2を使用する**
   - Docker Desktop設定で「Use WSL 2 based engine」を有効化

2. **ファイルシステムのパフォーマンス**
   - プロジェクトをWSL2のファイルシステム上に配置すると高速化

### 開発用の高速化
```bash
# Composer autoload最適化
docker-compose exec laravel.test composer dump-autoload -o

# ルートキャッシュ
docker-compose exec laravel.test php artisan route:cache

# 設定キャッシュ
docker-compose exec laravel.test php artisan config:cache
```

## セキュリティ注意事項

本番環境で使用する場合:
1. `.env`ファイルの`APP_KEY`を変更
2. データベースのパスワードを強力なものに変更
3. `APP_DEBUG=false`に設定
4. 適切なファイアウォール設定
