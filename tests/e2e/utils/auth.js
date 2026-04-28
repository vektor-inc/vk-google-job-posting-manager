/**
 * 管理者ログイン用の共通ユーティリティ。
 *
 * @wordpress/e2e-test-utils-playwright を使えば admin fixture が利用できるが、
 * 設定や依存の都合で pure Playwright のみで動かす場面もあるため、
 * フォールバック用としてこのヘルパーを用意する。
 */

/**
 * wp-login.php 経由で admin ログインする。
 *
 * @param {import('@playwright/test').Page} page
 * @param {Object}                          [options]
 * @param {string}                          [options.username='admin']
 * @param {string}                          [options.password='password']
 */
async function loginAsAdmin( page, options = {} ) {
	const username = options.username || 'admin';
	const password = options.password || 'password';

	await page.goto( '/wp-login.php' );

	// ログイン済みなら wp-admin にリダイレクトされるためスキップ。
	if ( page.url().includes( '/wp-admin' ) ) {
		return;
	}

	await page.fill( '#user_login', username );
	await page.fill( '#user_pass', password );
	await page.click( '#wp-submit' );

	// ダッシュボードまたはサイトの管理画面が表示されるまで待つ。
	await page.waitForURL( /\/wp-admin\/?/, { timeout: 15000 } );
}

module.exports = { loginAsAdmin };
