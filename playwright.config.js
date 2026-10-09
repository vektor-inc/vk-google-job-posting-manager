/**
 * Playwright 設定ファイル
 *
 * - baseURL は WP_BASE_URL 環境変数で上書き可能。
 *   baseURL must point at the wp-env *tests* site, because tests/e2e/utils/wp-cli.js
 *   creates test data via `tests-cli`. Locally, set `testsPort` to 9151 in
 *   .wp-env.override.json (the default below). CI passes WP_BASE_URL (8889).
 *   baseURL は wp-env の「テスト用サイト」を指している前提とする
 *   （tests/e2e/utils/wp-cli.js が tests-cli でテストデータを作るため）。
 *   ローカルでは .wp-env.override.json で testsPort を 9151 にすること
 *   （下記のデフォルト値）。CI 環境では GitHub Actions 側で WP_BASE_URL（8889）を渡す。
 * - テストファイル内で page.goto() に絶対 URL をハードコードしないこと
 *   （rules/testing/e2e.md のルール）。
 *
 * @see https://playwright.dev/docs/test-configuration
 */

const { defineConfig, devices } = require( '@playwright/test' );

module.exports = defineConfig( {
	// e2e テストの配置先 / Where e2e specs live.
	testDir: './tests/e2e/specs',

	// 1 ファイル内のテストを順次実行（投稿作成→公開ページ確認のような
	// 状態を引き継ぐシナリオがあるため、parallel ではなく serial にする）。
	fullyParallel: false,

	// CI 上で .only を残したテストがあれば失敗させる。
	forbidOnly: !! process.env.CI,

	// CI ではフレーク対策で 2 回までリトライする。
	retries: process.env.CI ? 2 : 0,

	// ローカルは並列度 1。WordPress のグローバル状態（投稿・オプション）を
	// 共有しているため、ワーカーを増やすと相互干渉する。
	workers: 1,

	// Reporter / レポーター
	reporter: process.env.CI ? [ [ 'github' ], [ 'html', { open: 'never' } ] ] : 'list',

	// 各テスト共通の設定 / Shared test settings.
	use: {
		// ベース URL は環境変数で切替可能にする（CI とローカルでポートが異なるため）。
		// Must be the wp-env tests site (testsPort). / wp-env のテスト用サイト（testsPort）を指すこと。
		baseURL: process.env.WP_BASE_URL || 'http://localhost:9151',

		// 失敗時のみトレース / Trace only on first retry to keep CI fast.
		trace: 'retain-on-failure',

		// 失敗時のみスクリーンショット / Screenshot only on failure.
		screenshot: 'only-on-failure',

		// HTTPS 証明書検証は無効（wp-env はオレオレ証明書になり得るため）。
		ignoreHTTPSErrors: true,
	},

	// 個別テストのタイムアウト（WordPress エディタの起動が遅いことがあるため少し長め）。
	timeout: 60 * 1000,
	expect: {
		timeout: 10 * 1000,
	},

	// 対象ブラウザ / Target browsers.
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
