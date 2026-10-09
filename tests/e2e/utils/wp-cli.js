/**
 * wp-env 経由で wp-cli コマンドを実行するユーティリティ。
 *
 * Playwright のテスト中で投稿作成・オプション操作・クリーンアップを
 * 手早く行いたい場合に使う。`npx wp-env run tests-cli wp ...` を子プロセスで実行する。
 *
 * Target site / 対象サイト:
 *  - Data is created on the wp-env *tests* site (tests-cli), because the CI
 *    workflow points Playwright's baseURL (WP_BASE_URL) at the tests site (8889).
 *    Creating data on the development site (cli, 8888) while baseURL points at
 *    the tests site makes relative-path requests hit a site without that data.
 *  - CI のワークフローは baseURL（WP_BASE_URL）を wp-env のテスト用サイト（8889）に
 *    向けているため、データもテスト用サイト（tests-cli）に作る。開発用サイト（cli / 8888）
 *    にデータを作ると、相対パスでのアクセス先（baseURL）にデータが無い状態になるため。
 *
 * 実装メモ:
 *  - 文字列連結 + execSync は引用符・空白・特殊文字でシェルが壊れる可能性が
 *    あるため、引数配列ベースの spawnSync を使用する。
 *  - 失敗時は stderr を含めた Error を throw する。
 */

const { spawnSync } = require( 'child_process' );
const path = require( 'path' );

// プラグインルートを基準に wp-env を呼ぶ（package.json と .wp-env.json がある場所）。
const PLUGIN_ROOT = path.resolve( __dirname, '../../..' );

/**
 * wp-cli コマンドを実行して標準出力を返す。
 *
 * 使用例:
 *   wpCli( [ 'post', 'create', '--post_type=job-posts', '--post_status=publish', '--porcelain' ] );
 *   wpCli( [ 'post', 'meta', 'update', postId, 'vkjp_title', 'Some title' ] );
 *
 * 引数は配列で渡すこと。シェルを介さないため、空白や引用符を含む値も
 * そのまま渡せる（個別引数として安全に扱われる）。
 *
 * @param {string[]} args wp の後ろに続く引数の配列。
 * @returns {string} stdout の文字列（trim 済み）。
 * @throws  {Error}  終了コードが 0 以外、またはプロセス起動に失敗した場合。
 */
function wpCli( args ) {
	if ( ! Array.isArray( args ) ) {
		throw new TypeError( 'wpCli() requires an array of arguments.' );
	}

	// `npx wp-env run tests-cli wp <args...>` を引数配列で起動する。
	// shell: false（既定）にすることで、引数ごとの境界が保たれる。
	// Run against the tests site so that data matches baseURL (see file header).
	// baseURL と同じテスト用サイトに対して実行する（ファイル冒頭のコメント参照）。
	const result = spawnSync(
		'npx',
		[ 'wp-env', 'run', 'tests-cli', 'wp', ...args ],
		{
			cwd: PLUGIN_ROOT,
			encoding: 'utf8',
			stdio: [ 'ignore', 'pipe', 'pipe' ],
		}
	);

	// プロセス自体の起動に失敗した場合（npx 不在等）。
	if ( result.error ) {
		throw new Error(
			`Failed to spawn wp-cli (wp ${ args.join( ' ' ) }): ${ result.error.message }`
		);
	}

	if ( result.status !== 0 ) {
		const stderr = ( result.stderr || '' ).trim();
		const stdout = ( result.stdout || '' ).trim();
		throw new Error(
			`wp-cli exited with status ${ result.status } (wp ${ args.join( ' ' ) }):\n` +
				`stderr: ${ stderr }\nstdout: ${ stdout }`
		);
	}

	return ( result.stdout || '' ).trim();
}

/**
 * 求人情報投稿タイプ（job-posts）が有効になっていることを保証する。
 * デフォルト有効だが、テスト独立性のため明示的に on にしておく。
 *
 * Why the rewrite flush is needed / rewrite flush が必要な理由:
 *  - On a fresh wp-env, `wp core install` enables pretty permalinks and stores
 *    the rewrite rules *before* the plugin is activated. The activation hook only
 *    sets the option, and `job-posts` is registered on `init` only when that option
 *    was already 'true' at load time, so its rules are never written. As a result
 *    `/job-posts/<slug>/` returns 404 until the rules are regenerated.
 *  - 新しい wp-env では `wp core install` の時点でパーマリンク（URL ルール）が作られ、
 *    その後にプラグインが有効化される。有効化処理はオプションを保存するだけで、
 *    `job-posts` はオプションが読み込み時点で 'true' のときにしか `init` で登録されない
 *    ため、job-posts の URL ルールが作られず `/job-posts/<slug>/` が 404 になる。
 *  - The flush runs in a separate wp-cli process after the option update, so the
 *    post type is registered when the rules are rebuilt.
 *    オプション更新とは別の wp-cli プロセスで flush するため、作り直しの時点で
 *    job-posts が登録済みになり、URL ルールに含まれる。
 */
function ensureJobPostsEnabled() {
	wpCli( [ 'option', 'update', 'vgjpm_create_jobpost_posttype', 'true' ] );
	wpCli( [ 'rewrite', 'flush' ] );
}

/**
 * 投稿の個別ページ URL を、baseURL からの相対パス（パス + クエリ）で返す。
 *
 * Returns the permalink of a post as a path relative to baseURL.
 * `wp post url` returns an absolute URL built from the site's WP_SITEURL, whose
 * port may differ from baseURL (CI and local use different ports). Requests must
 * go through baseURL (rules/testing/e2e.md), so only the path and query are kept.
 * `wp post url` はサイトの WP_SITEURL を元にした絶対 URL を返し、そのポートは
 * baseURL と一致するとは限らない（CI とローカルでポートが異なる）。アクセスは
 * baseURL 経由で行う決まりのため、パスとクエリだけを取り出して返す。
 *
 * @param {number|string} postId
 * @returns {string} 例: `/job-posts/e2e-empty-job-posting/`
 */
function getPostPath( postId ) {
	const permalink = wpCli( [ 'post', 'url', String( postId ) ] );
	const parsed = new URL( permalink );
	return `${ parsed.pathname }${ parsed.search }`;
}

/**
 * 投稿を ID 指定で削除する（trash ではなく force delete）。
 *
 * 「投稿が既に存在しない」エラーは afterEach のクリーンアップで頻繁に
 * 発生するため握りつぶすが、それ以外（wp-env 障害・コマンド誤り等）は
 * 黙って通すと不具合の見落としに繋がるので再 throw する。
 *
 * @param {number|string} postId
 * @throws {Error} 「存在しない」以外の wp-cli エラーが発生した場合。
 */
function deletePost( postId ) {
	try {
		wpCli( [ 'post', 'delete', String( postId ), '--force' ] );
	} catch ( err ) {
		// wp-cli は対象が無いとき "Error: Could not find the post with ID xxx."
		// "post does not exist" などのメッセージを返す。
		// メッセージからこれらのマーカーを検出した場合のみ無視する。
		const message = ( err && err.message ) ? err.message.toLowerCase() : '';
		const isMissing =
			message.includes( 'could not find the post' ) ||
			message.includes( 'does not exist' ) ||
			message.includes( 'not found' ) ||
			message.includes( 'invalid post id' );

		if ( ! isMissing ) {
			throw err;
		}
	}
}

module.exports = { wpCli, ensureJobPostsEnabled, getPostPath, deletePost };
