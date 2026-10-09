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

e2e テストは、wp-env の **テスト用サイト**（`tests-cli` / `tests-wordpress`）に wp-cli でテストデータを作り、`baseURL` からの相対パスでそのサイトを開きます。そのため `baseURL` はテスト用サイトを指している必要があります。

- `playwright.config.js` の `baseURL` の既定値は `http://localhost:9151` です。ローカルでは `.wp-env.override.json` で **`testsPort` を 9151** にしてください（開発用サイトの `port` は任意）。

```json
{
    "port": 9150,
    "testsPort": 9151
}
```

- 別のポートを使う場合は、`WP_BASE_URL` にテスト用サイトの URL を指定してください（CI では既定の testsPort である 8889 を指定しています）。
- wp-cli の実行先コンテナは `WP_ENV_CLI_CONTAINER` で変更できます（既定は `tests-cli`）。変更する場合は、`WP_BASE_URL` もそのコンテナのサイトに合わせてください。

```bash
# 初回のみ: ブラウザのインストール
npm run test:e2e:install

# wp-env を起動した状態で実行（.wp-env.override.json で testsPort を 9151 にしておく）
npx wp-env start
npm run test:e2e

# 別のポートのテスト用サイトに対して実行する場合は WP_BASE_URL を指定
WP_BASE_URL=http://localhost:8889 npm run test:e2e
```

**phpunit を実行した後の注意:** `npm run phpunit` を実行すると、テスト用サイトの DB（`tests-wordpress`）のテーブルが作り直され、プラグインが無効になり、パーマリンクも初期状態に戻ります。phpunit の後に e2e テストを実行するときは、先に `npx wp-env start --update` でテスト用サイトをセットアップし直してください。

```bash
npx wp-env start --update
npm run test:e2e
```
