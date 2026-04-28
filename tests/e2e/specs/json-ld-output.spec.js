/**
 * JSON-LD 出力に関する e2e テスト
 *
 * issue #123 / PR #124 の不具合（求人情報未入力でも空の JobPosting 構造化データが
 * 出力され Search Console エラーが起きる）に対する回帰テスト。
 *
 * 観点:
 *  1. 求人情報サイドバー未入力で公開した投稿で <script type="application/ld+json">
 *     が出力されないこと
 *  2. 求人情報を入力して公開した投稿で従来通り JSON-LD が出力されること
 *  3. アーカイブページ・対象外投稿タイプで JSON-LD が出ないこと
 *
 * 実装メモ:
 *  - 投稿作成・meta 設定・クリーンアップは wp-cli 経由で行う。
 *    ブロックエディタ UI を経由した REST 保存は手順が長く、
 *    JSON-LD 出力（フロント側 wp_head）の検証目的では不要なため。
 *  - JSON-LD の有無判定はフロントの HTML を取得し
 *    `<script type="application/ld+json">` 文字列の有無でチェックする。
 *
 * 前提: PR #124 の修正がマージ済みであること。本ブランチは master ベースのため、
 *      PR #124 がマージされる前にこの spec を流すと「未入力ケースで JSON-LD
 *      が出力される」=> JSON-LD 抑止テストが FAIL する想定。
 *      PR #124 マージ後にリベースすれば全テスト PASS する。
 */

const { test, expect } = require( '@playwright/test' );
const { wpCli, ensureJobPostsEnabled, deletePost } = require( '../utils/wp-cli' );

// 各テストで作成した投稿 ID を集めておき、afterEach で一括削除する。
let createdPostIds = [];

/**
 * 指定 URL のフロント HTML を fetch して返す（Playwright の page.request を使用）。
 *
 * @param {import('@playwright/test').APIRequestContext} request
 * @param {string}                                       url     相対パスでも絶対パスでも可
 * @returns {Promise<string>}
 */
async function fetchHtml( request, url ) {
	const response = await request.get( url );
	expect( response.ok(), `フロント取得失敗: ${ url } (status=${ response.status() })` ).toBeTruthy();
	return await response.text();
}

/**
 * HTML 内に JobPosting JSON-LD（<script type="application/ld+json">）が
 * 含まれているかを返す。
 *
 * @param {string} html
 * @returns {boolean}
 */
function hasJsonLd( html ) {
	return /<script\s+type=["']application\/ld\+json["']/i.test( html );
}

test.describe( 'JSON-LD 出力（issue #123 / PR #124 回帰テスト）', () => {
	test.beforeAll( () => {
		// テストの独立性確保のため、求人情報 CPT を有効化しておく。
		ensureJobPostsEnabled();
	} );

	test.afterEach( () => {
		// テストで作成した投稿を全削除（DB に残骸を残さない）。
		for ( const id of createdPostIds ) {
			deletePost( id );
		}
		createdPostIds = [];
	} );

	test( '未入力で公開した job-posts では JSON-LD が出力されない', async ( { request } ) => {
		// 求人情報を一切入力せず、タイトルのみで job-posts を公開する。
		// 修正前は post meta `vkjp_title` がサイドバーパネル経由で空文字保存され、
		// `isset()` ガードを通過してしまうため空の JobPosting が出力されていた。
		const postId = wpCli(
			"post create --post_type=job-posts --post_status=publish --post_title='E2E: empty job posting' --porcelain"
		);
		expect( postId ).toMatch( /^\d+$/ );
		createdPostIds.push( postId );

		// 修正のシミュレーション: 空文字 meta を REST 経由保存と同じように差し込む。
		// （wp-cli から meta を空文字で update する）
		wpCli( `post meta update ${ postId } vkjp_title ''` );

		// フロントの HTML を取得し JSON-LD が無いことを確認する。
		const url = wpCli( `post url ${ postId }` );
		const html = await fetchHtml( request, url );

		expect(
			hasJsonLd( html ),
			'未入力の求人情報投稿で JSON-LD が出力されてはいけない（issue #123）'
		).toBe( false );
	} );

	test( '空白のみのタイトルで公開した job-posts では JSON-LD が出力されない', async ( { request } ) => {
		// 半角スペース / タブ / 全角スペースのみのタイトルも「未入力」扱いとする
		// （PR #124 の f4b3711 で対応された強化分）。
		const postId = wpCli(
			"post create --post_type=job-posts --post_status=publish --post_title='E2E: whitespace job title' --porcelain"
		);
		createdPostIds.push( postId );

		// 半角スペース + タブ + 全角スペース。
		wpCli( `post meta update ${ postId } vkjp_title ' \t　'` );

		const url = wpCli( `post url ${ postId }` );
		const html = await fetchHtml( request, url );

		expect(
			hasJsonLd( html ),
			'空白文字のみのタイトルでも JSON-LD は出力されてはいけない'
		).toBe( false );
	} );

	test( '求人情報を入力した job-posts では JSON-LD が従来通り出力される', async ( { request } ) => {
		// 必須項目（title）と最低限の補助項目を入力した投稿を作成し、
		// 従来通り JobPosting JSON-LD が出力されることを確認する（デグレチェック）。
		const postId = wpCli(
			"post create --post_type=job-posts --post_status=publish --post_title='E2E: filled job posting' --post_content='Test job description' --porcelain"
		);
		createdPostIds.push( postId );

		// 求人情報フィールドを wp-cli で投入。
		wpCli( `post meta update ${ postId } vkjp_title 'Senior WordPress Engineer'` );
		wpCli( `post meta update ${ postId } vkjp_description 'We are hiring!'` );
		wpCli( `post meta update ${ postId } vkjp_datePosted '2026-04-01'` );
		wpCli( `post meta update ${ postId } vkjp_validThrough '2026-12-31'` );
		wpCli( `post meta update ${ postId } vkjp_name 'Vektor, Inc.'` );

		const url = wpCli( `post url ${ postId }` );
		const html = await fetchHtml( request, url );

		expect(
			hasJsonLd( html ),
			'求人情報を入力済みの job-posts では JSON-LD が出力されるはず（デグレ防止）'
		).toBe( true );

		// 中身も簡易チェック: JobPosting タイプと title 文字列を含むこと。
		const match = html.match( /<script\s+type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/i );
		expect( match, 'application/ld+json の <script> ブロックが取得できること' ).not.toBeNull();
		const jsonText = match[ 1 ].trim();
		expect( jsonText ).toContain( 'JobPosting' );
		expect( jsonText ).toContain( 'Senior WordPress Engineer' );
	} );

	test( 'アーカイブページでは JSON-LD が出力されない', async ( { request } ) => {
		// アーカイブ（投稿一覧）には JobPosting JSON-LD が出るべきではない。
		// 入力済みの job-posts を 1 件作成してから、その投稿タイプアーカイブを開く。
		const postId = wpCli(
			"post create --post_type=job-posts --post_status=publish --post_title='E2E: archive check posting' --porcelain"
		);
		createdPostIds.push( postId );
		wpCli( `post meta update ${ postId } vkjp_title 'Archive Check Title'` );

		// job-posts のアーカイブ（パーマリンク設定によっては /?post_type=job-posts でアクセス可）。
		const html = await fetchHtml( request, '/?post_type=job-posts' );

		expect(
			hasJsonLd( html ),
			'アーカイブページに JSON-LD が出力されてはいけない'
		).toBe( false );
	} );

	test( '対象外投稿タイプ（page）の個別ページでは JSON-LD が出力されない', async ( { request } ) => {
		// 求人情報メタボックスを有効化していない page 投稿タイプでは、
		// たとえ何らかの経路で vkjp_title meta が付いても JSON-LD は出してはいけない。
		const pageId = wpCli(
			"post create --post_type=page --post_status=publish --post_title='E2E: out-of-scope page' --porcelain"
		);
		createdPostIds.push( pageId );

		// page でも誤って vkjp_title meta が入った状況をシミュレートする
		// （`vgjpm_print_jsonLD_in_footer` の post type ガードがあるため出力されないはず）。
		wpCli( `post meta update ${ pageId } vkjp_title 'Should not output JSON-LD'` );

		const url = wpCli( `post url ${ pageId }` );
		const html = await fetchHtml( request, url );

		expect(
			hasJsonLd( html ),
			'対象外の投稿タイプ（page）で JSON-LD が出力されてはいけない'
		).toBe( false );
	} );
} );
