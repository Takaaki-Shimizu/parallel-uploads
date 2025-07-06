# 並行ファイルアップロードシステム

Laravel 10 + Alpine.js + Tailwind CSS で構築された並行ファイルアップロードシステム

## システム概要

- **最大10ファイル**（システム全体での制限）
- **1ファイル最大1MB**
- **並行アップロード処理**（最大3ファイル同時）
- **失敗時の自動リトライ**（最大3回）
- **失敗ファイル表示**（セッション内で確認可能、手動クリア対応）
- **日本語UI**（レスポンシブデザイン対応）

## アップロード処理の詳細フロー

「アップロード開始」ボタン押下から「アップロード済みファイル」欄に表示されるまでの処理

### 1. ファイル選択段階（事前準備）

```javascript
// fileUpload.js - handleFiles()
```

1. **ファイル選択時の処理**
   - ドラッグ&ドロップまたはファイル選択
   - `handleFiles(files)` 実行
   - クライアント側バリデーション（ファイル数、サイズ、既存ファイル数チェック）
   - `selectedFiles`に保存、UI表示用の`files`配列作成（status: 'selected'）

### 2. アップロード開始ボタン押下

```javascript
// fileUpload.js - startUpload()
```

#### 2-1. ファイル登録フェーズ
1. **サーバーへファイル情報登録**
   ```javascript
   const registeredFiles = await window.fileUploader.registerFiles(this.selectedFiles);
   ```
   
2. **API: POST /api/files/upload**
   ```php
   // FileUploadController::upload()
   ```
   - バリデーション（10ファイル制限、1MB制限）
   - 既存完了ファイル数 + 新規ファイル数が10以下かチェック
   - 各ファイルのUUID生成、データベース記録作成（status: 'pending'）
   - レスポンス: 登録済みファイル情報の配列

3. **UI更新**
   ```javascript
   this.files = registeredFiles.map(file => ({
       ...file,
       progress: 0,
       status: 'pending'
   }));
   ```

#### 2-2. 並行アップロード実行フェーズ
1. **並行アップロード開始**
   ```javascript
   await window.fileUploader.processParallelUploads(this.selectedFiles, registeredFiles);
   ```

2. **ParallelFileUploader::processParallelUploads()**
   - ファイルマッピング作成（DB ID ⟷ File オブジェクト）
   - アップロードキュー初期化
   - 最大3つの並行処理スレッド開始

### 3. 各ファイルの個別アップロード処理

```javascript
// ParallelFileUploader::processNextUpload() → uploadSingleFile()
```

#### 3-1. ファイル処理開始
1. **キューからファイル取得**
   ```javascript
   const fileInfo = this.uploadQueue.shift();
   ```

2. **ステータス更新**
   ```javascript
   this.updateFileStatus(fileInfo.id, { status: 'uploading', progress: 0 });
   ```
   - UIに「アップロード中」表示

#### 3-2. サーバーサイド処理
1. **API: POST /api/files/{id}/process**
   ```php
   // FileUploadController::processUpload()
   ```

2. **アップロード実行**
   - DB: status を 'uploading' に更新、started_at 記録
   - ファイル実体をストレージ保存（`storage/app/public/uploads/`）
   - DB: status を 'completed' に更新、completed_at 記録

3. **レスポンス**
   ```json
   {
       "message": "File uploaded successfully",
       "file": { /* 更新されたファイル情報 */ }
   }
   ```

#### 3-3. クライアント側完了処理
1. **ステータス更新**
   ```javascript
   this.updateFileStatus(fileInfo.id, { 
       status: 'completed', 
       progress: 100,
       ...data.file 
   });
   ```

2. **UI更新イベント発火**
   ```javascript
   document.dispatchEvent(new CustomEvent('fileStatusUpdated', {
       detail: { fileId, status: newStatus }
   }));
   ```

### 4. 失敗時のリトライ処理

#### 4-1. 失敗検知
```javascript
// ParallelFileUploader::handleUploadFailure()
```
- エラーキャッチ時に実行
- リトライ回数カウントアップ
- 最大3回まで自動リトライ

#### 4-2. 自動リトライ実行
1. **リトライAPI呼び出し**
   ```javascript
   // API: POST /api/files/{id}/retry
   ```
   
2. **サーバー側リトライ準備**
   ```php
   // FileUploadController::retry()
   ```
   - DB: status を 'pending' に戻す
   - error_message をクリア

3. **再キューイング**
   ```javascript
   this.uploadQueue.push(data.file);
   this.processNextUpload();
   ```

### 5. 失敗ファイルの管理

```javascript
// fileUpload.js - fileStatusUpdated イベント
```

#### 5-1. 失敗ファイルの検知と保存
1. **失敗ステータスの検知**
   ```javascript
   if (status.status === 'failed') {
       const failedFile = { ...this.files[fileIndex] };
       if (!this.failedFiles.find(f => f.id === fileId)) {
           this.failedFiles.push(failedFile);
       }
   }
   ```

2. **失敗ファイル表示**
   - ドラッグ&ドロップエリアとファイル一覧の間に表示
   - セッション内で永続化（ページリロードまで）
   - エラーメッセージと詳細情報を表示

#### 5-2. 失敗ファイルのクリア
1. **手動クリア**
   ```javascript
   @click="failedFiles = []"  // クリアボタン
   ```

2. **自動クリア**
   ```javascript
   this.failedFiles = [];     // 新しいファイル選択時
   ```

### 6. 全ファイル完了後の処理

```javascript
// fileUpload.js - startUpload() 完了部分
```

#### 6-1. アップロード済みファイル一覧更新
1. **最新ファイル一覧取得**
   ```javascript
   await this.loadUploadedFiles();
   ```

2. **API: GET /api/files**
   ```php
   // FileUploadController::index()
   ```
   - 全ファイル取得（created_at 降順）
   - 完了ステータスのファイルのみフィルタリング

3. **UI表示更新**
   ```javascript
   this.uploadedFiles = completedFiles.sort((a, b) => {
       return new Date(b.completed_at || b.created_at) - new Date(a.completed_at || a.created_at);
   });
   ```

#### 6-2. UI クリーンアップ
```javascript
this.files = [];           // 現在のアップロード表示をクリア
this.selectedFiles = null; // 選択ファイルをクリア
this.uploading = false;    // アップロード中フラグをオフ
// 注意: failedFiles は保持される（手動クリアまで）
```

### 7. レスポンシブレイアウト表示

#### 7-1. デスクトップ（Z字パターン）
```javascript
get leftFiles() {
    // 1,3,5,7,9番目のファイル（奇数番目）
    return this.uploadedFiles.filter((file, index) => index % 2 === 0);
}

get rightFiles() {
    // 2,4,6,8,10番目のファイル（偶数番目）
    return this.uploadedFiles.filter((file, index) => index % 2 === 1);
}
```

#### 7-2. モバイル（順次表示）
```html
<!-- 1から10まで順番表示 -->
<div class="lg:hidden space-y-3">
    <template x-for="(file, index) in uploadedFiles">
```

## 主要コンポーネント

### バックエンド
- **FileUpload Model**: ファイル情報・ステータス管理
- **FileUploadController**: アップロード処理・API提供
- **Migration**: file_uploads テーブル

### フロントエンド
- **ParallelFileUploader Class**: 並行アップロード制御・自動リトライ処理
- **Alpine.js Component**: UI状態管理・イベント処理・失敗ファイル管理
- **upload.blade.php**: メインUI・レスポンシブレイアウト・失敗ファイル表示

## API エンドポイント

| Method | Endpoint | 説明 |
|--------|----------|------|
| POST | `/api/files/upload` | ファイル情報登録 |
| POST | `/api/files/{id}/process` | 個別ファイルアップロード |
| POST | `/api/files/{id}/retry` | アップロード失敗時のリトライ |
| GET | `/api/files` | アップロード済みファイル一覧 |
| GET | `/api/files/{id}/download` | ファイルダウンロード |
| DELETE | `/api/files/{id}` | ファイル削除 |

## UI セクション構成

### 1. アップロード済みファイル
- **表示条件**: 常時表示
- **レイアウト**: デスクトップ（Z字パターン）、モバイル（順次）
- **機能**: ダウンロード、削除

### 2. アップロード失敗したファイル
- **表示条件**: 失敗ファイルがある場合のみ
- **位置**: ドラッグ&ドロップエリアとファイル一覧の間
- **機能**: エラー内容表示、手動クリア
- **永続性**: セッション内（ページリロードまで）

### 3. ドラッグ&ドロップエリア
- **機能**: ファイル選択、ドラッグ&ドロップ対応
- **制限表示**: 10ファイル上限時は無効化

### 4. ファイル一覧
- **表示条件**: 常時表示
- **機能**: 選択ファイルの確認、進捗表示

### 5. ステータス統計
- **表示条件**: 常時表示
- **機能**: 選択済み、待機中、アップロード中、完了、失敗の数値表示

## 開発コマンド

```bash
# 開発サーバー起動
php artisan serve

# フロントエンド開発サーバー
npm run dev

# マイグレーション実行
php artisan migrate

# ストレージリンク作成
php artisan storage:link

# コード整形
./vendor/bin/pint
```