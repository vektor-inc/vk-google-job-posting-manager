# vk-google-job-posting-manager

ビルド
```bash
npm run build
```

PHPUnitテスト
```bash
npm run phpunit
```

Playwright e2e テスト
```bash
# 初回のみ: ブラウザのインストール
npm run test:e2e:install

# wp-env を起動した状態で実行（.wp-env.override.json では 9151 ポートを使用）
npx wp-env start
npm run test:e2e

# CI 等でポートを変えたい場合は WP_BASE_URL を指定
WP_BASE_URL=http://localhost:8889 npm run test:e2e
```
