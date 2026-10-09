#!/bin/bash
#
# Verify that a release has actually been published on WordPress.org.
# WordPress.org に公開したバージョンが実際に反映されたかを確認する。
#
# Usage / 使い方:
#   bin/verify-wporg-release.sh <slug> <expected-version>
#
# Arguments may also be given as environment variables.
# 引数の代わりに環境変数でも指定できる。
#   SLUG                    Plugin slug / プラグインのスラッグ
#   EXPECTED_VERSION        Version that should be published / 公開されているはずのバージョン
#   WPORG_VERIFY_INTERVAL   Seconds between checks (default: 60) / 問い合わせ間隔（秒）
#   WPORG_VERIFY_TIMEOUT    Max seconds to wait (default: 1800) / 待つ上限（秒）
#
# Exit status / 終了ステータス:
#   0  The expected version is published (HTTP 200). / 期待したバージョンが公開されている
#      （HTTP 200 の応答で確認）。
#   1  The API returned an error (e.g. "closed") with HTTP 200 / 404, or the version
#      did not match before the timeout. / API が HTTP 200 / 404 で error を返した
#      （closed など）、または上限までにバージョンが一致しなかった。
#   2  Invalid arguments, or a temporary file could not be created.
#      / 引数が不正、または一時ファイルを作成できなかった。
#
# Transient network errors, responses that are not valid JSON, and "error" responses
# with any HTTP status other than 200 / 404 (5xx, rate limiting, ...), and non-200
# responses without "error" (even if the version matches) are retried until the timeout.
# / 一時的な通信エラー、JSON として読めない応答、HTTP 200 / 404 以外（5xx やレート制限など）で
# 返った error、error の無い HTTP 200 以外の応答（バージョンが一致していても）は上限まで再試行する。

set -u

# Read arguments, falling back to environment variables.
# 引数を読み込む。無ければ環境変数を使う。
slug="${1:-${SLUG:-}}"
expected_version="${2:-${EXPECTED_VERSION:-}}"
interval="${WPORG_VERIFY_INTERVAL:-60}"
timeout="${WPORG_VERIFY_TIMEOUT:-1800}"

# Validate arguments.
# 引数を検証する。
if [[ -z "${slug}" || -z "${expected_version}" ]]; then
	echo "Usage: $0 <slug> <expected-version>" >&2
	exit 2
fi
# The slug is used in a URL, so allow only the characters a slug can contain.
# スラッグは URL に埋め込むため、スラッグに使える文字だけを許可する。
if [[ ! "${slug}" =~ ^[a-z0-9-]+$ ]]; then
	echo "::error::Invalid slug: ${slug}"
	exit 2
fi
# Reject leading zeros (e.g. "08"), which bash arithmetic would treat as octal.
# 先頭が 0 の値（例: "08"）は bash の算術式で8進数扱いになるため弾く。
if [[ ! "${interval}" =~ ^[1-9][0-9]*$ || ! "${timeout}" =~ ^(0|[1-9][0-9]*)$ ]]; then
	echo "::error::WPORG_VERIFY_INTERVAL must be a positive integer and WPORG_VERIFY_TIMEOUT a non-negative integer."
	exit 2
fi

api_url="https://api.wordpress.org/plugins/info/1.0/${slug}.json"
deadline=$(( $(date +%s) + timeout ))
attempt=0
# The last observed state, shown when the timeout is reached.
# 上限到達時に表示する、最後に確認できた状態。
last_state="no response yet"

# Temporary file that receives curl's error messages, removed on exit.
# curl のエラーメッセージを受け取る一時ファイル。終了時に削除する。
# Without it curl could never run and the script would just wait until the timeout.
# 作成できないと curl が一度も実行されず上限まで待つだけになるため、すぐ止める。
curl_stderr=$(mktemp) || { echo "::error::Failed to create a temporary file."; exit 2; }
trap 'rm -f "${curl_stderr}"' EXIT

# jq filter that makes an API string safe to print: control characters (including
# newlines, which could start a workflow command such as "::add-mask::") become
# spaces, and the length is limited.
# API の文字列を安全に出力するための jq フィルタ。改行を含む制御文字は空白に置き換え
# （改行があると "::add-mask::" などのワークフローコマンドを行頭に出せてしまうため）、長さも切り詰める。
# "[[:cntrl:]]" is used because jq's regex engine (Oniguruma) does not read "\u0000"-style
# ranges as intended and would replace ordinary characters too.
# jq の正規表現（Oniguruma）は "\u0000" 形式の範囲を意図どおりに解釈せず通常の文字まで
# 置き換えてしまうため、"[[:cntrl:]]" を使う。
sanitize='tostring | gsub("[[:cntrl:]]"; " ") | .[0:$max]'

echo "Checking ${api_url} until version ${expected_version} is published (interval: ${interval}s, timeout: ${timeout}s)."

while true; do
	attempt=$(( attempt + 1 ))

	# Fetch the plugin information. The HTTP status code is appended on its own line.
	# A 404 also returns a JSON body with "error", so do not use --fail.
	# プラグイン情報を取得する。HTTP ステータスコードを最終行に付け足す。
	# 404 でも "error" を含む JSON が返るため --fail は使わない。
	# Errors go to a separate file so they do not mix with the status code, and
	# redirects are limited to HTTPS.
	# エラーはステータスコードと混ざらないよう別ファイルに受け取り、リダイレクト先は HTTPS に限る。
	if response=$(curl --silent --show-error --location --proto '=https' --proto-redir '=https' \
		--connect-timeout 10 --max-time 20 \
		--write-out '\n%{http_code}' "${api_url}" 2>"${curl_stderr}"); then
		http_code="${response##*$'\n'}"
		body="${response%$'\n'*}"

		# Only a JSON object can be judged. Anything else is treated as transient.
		# 判定できるのは JSON オブジェクトだけ。それ以外は一時的な異常として再試行する。
		if jq -e 'type == "object"' >/dev/null 2>&1 <<<"${body}"; then
			api_error=$(jq -r --argjson max 200 ".error // empty | ${sanitize}" <<<"${body}")
			published_version=$(jq -r --argjson max 50 ".version // empty | ${sanitize}" <<<"${body}")

			if [[ -n "${api_error}" ]]; then
				# Only 200 / 404 mean the API has answered definitively (e.g. the plugin is
				# closed or not found), so waiting will not help.
				# 200 / 404 のときだけ API の確定した回答（公開停止・存在しない等）とみなし、すぐ失敗する。
				if [[ "${http_code}" == "200" || "${http_code}" == "404" ]]; then
					echo "::error::WordPress.org API returned an error for ${slug} (HTTP ${http_code}): \"${api_error}\". The plugin may be closed (or the slug is wrong), so the release has not been distributed."
					exit 1
				fi
				# Other status codes (5xx, rate limiting, ...) may be temporary, so retry.
				# それ以外（5xx やレート制限など）は一時的な可能性があるため再試行する。
				last_state="API returned an error (HTTP ${http_code}): \"${api_error}\""
			elif [[ "${http_code}" != "200" ]]; then
				# Only a 200 response is trusted as the published state; others are retried
				# even if the version matches.
				# 公開状態として信用するのは HTTP 200 の応答だけ。それ以外はバージョンが一致していても再試行する。
				last_state="unexpected HTTP ${http_code} (version ${published_version:-unknown})"
			elif [[ "${published_version}" == "${expected_version}" ]]; then
				# The expected version is published.
				# 期待したバージョンが公開されている。
				echo "Attempt ${attempt}: version ${published_version} is published on WordPress.org."
				exit 0
			else
				last_state="published version is ${published_version:-unknown}"
			fi
		else
			last_state="response was not a JSON object (HTTP ${http_code})"
		fi
	else
		# curl itself failed (DNS, connection, timeout, ...).
		# curl 自体が失敗した（名前解決・接続・タイムアウトなど）。
		# Use only the first line of the message, without control characters, limited in length.
		# メッセージは1行目だけを使い、制御文字を除いて長さを切り詰める。
		curl_error=$(head -n 1 "${curl_stderr}" | tr -d '\000-\037\177' | cut -c 1-200)
		last_state="request failed: ${curl_error:-unknown error}"
	fi

	echo "Attempt ${attempt}: ${last_state}; expected ${expected_version}."

	# Stop when the timeout is reached; otherwise wait without passing the deadline.
	# 上限に達したら失敗する。そうでなければ上限を超えない範囲で待つ。
	remaining=$(( deadline - $(date +%s) ))
	if (( remaining <= 0 )); then
		echo "::error::Version ${expected_version} of ${slug} was not published on WordPress.org within ${timeout} seconds (${last_state}). Check that the SVN deploy and the Stable tag in readme.txt are correct."
		exit 1
	fi
	sleep $(( remaining < interval ? remaining : interval ))
done
