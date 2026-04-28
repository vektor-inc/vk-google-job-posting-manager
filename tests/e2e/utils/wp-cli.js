/**
 * wp-env 経由で wp-cli コマンドを実行するユーティリティ。
 *
 * Playwright のテスト中で投稿作成・オプション操作・クリーンアップを
 * 手早く行いたい場合に使う。`npx wp-env run cli wp ...` を子プロセスで実行する。
 */

const { execSync } = require( 'child_process' );
const path = require( 'path' );

// プラグインルートを基準に wp-env を呼ぶ（package.json と .wp-env.json がある場所）。
const PLUGIN_ROOT = path.resolve( __dirname, '../../..' );

/**
 * wp-cli コマンドを実行して標準出力を返す。
 *
 * @param {string} command wp の後ろに続く部分（例: "post create --post_title=foo"）
 * @returns {string} stdout の文字列。
 */
function wpCli( command ) {
	const full = `npx wp-env run cli wp ${ command }`;
	return execSync( full, {
		cwd: PLUGIN_ROOT,
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'pipe' ],
	} ).trim();
}

/**
 * 求人情報投稿タイプ（job-posts）が有効になっていることを保証する。
 * デフォルト有効だが、テスト独立性のため明示的に on にしておく。
 */
function ensureJobPostsEnabled() {
	wpCli( "option update vgjpm_create_jobpost_posttype 'true'" );
}

/**
 * 投稿を ID 指定で削除する（trash ではなく force delete）。
 *
 * @param {number|string} postId
 */
function deletePost( postId ) {
	try {
		wpCli( `post delete ${ postId } --force` );
	} catch ( _e ) {
		// 既に削除済みの場合などは無視する。
	}
}

module.exports = { wpCli, ensureJobPostsEnabled, deletePost };
