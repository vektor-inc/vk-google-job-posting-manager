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
#   0  The expected version is published. / 期待したバージョンが公開されている。
#   1  The API returned an error (e.g. "closed"), or the version did not match
#      before the timeout. / API が error を返した（closed など）、または上限までに
#      バージョンが一致しなかった。
#   2  Invalid arguments. / 引数が不正。
#
# Transient network errors and responses that are not valid JSON are retried
# until the timeout. / 一時的な通信エラーや JSON として読めない応答は上限まで再試行する。

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
if [[ ! "${interval}" =~ ^[0-9]+$ || ! "${timeout}" =~ ^[0-9]+$ || "${interval}" -lt 1 ]]; then
	echo "::error::WPORG_VERIFY_INTERVAL must be a positive integer and WPORG_VERIFY_TIMEOUT a non-negative integer."
	exit 2
fi

api_url="https://api.wordpress.org/plugins/info/1.0/${slug}.json"
deadline=$(( $(date +%s) + timeout ))
attempt=0
# The last observed state, shown when the timeout is reached.
# 上限到達時に表示する、最後に確認できた状態。
last_state="no response yet"

echo "Checking ${api_url} until version ${expected_version} is published (interval: ${interval}s, timeout: ${timeout}s)."

while true; do
	attempt=$(( attempt + 1 ))

	# Fetch the plugin information. The HTTP status code is appended on its own line.
	# A 404 also returns a JSON body with "error", so do not use --fail.
	# プラグイン情報を取得する。HTTP ステータスコードを最終行に付け足す。
	# 404 でも "error" を含む JSON が返るため --fail は使わない。
	if response=$(curl --silent --show-error --location --connect-timeout 10 --max-time 20 \
		--write-out '\n%{http_code}' "${api_url}" 2>&1); then
		http_code="${response##*$'\n'}"
		body="${response%$'\n'*}"

		# Only a JSON object can be judged. Anything else is treated as transient.
		# 判定できるのは JSON オブジェクトだけ。それ以外は一時的な異常として再試行する。
		if jq -e 'type == "object"' >/dev/null 2>&1 <<<"${body}"; then
			api_error=$(jq -r '.error // empty | tostring' <<<"${body}")
			published_version=$(jq -r '.version // empty | tostring' <<<"${body}")

			# The API reports an error (e.g. the plugin is closed). Waiting will not help.
			# API が error を返した（公開停止など）。待っても変わらないのですぐ失敗する。
			if [[ -n "${api_error}" ]]; then
				echo "::error::WordPress.org API returned an error for ${slug} (HTTP ${http_code}): \"${api_error}\". The plugin may be closed (or the slug is wrong), so the release has not been distributed."
				exit 1
			fi

			# The expected version is published.
			# 期待したバージョンが公開されている。
			if [[ "${published_version}" == "${expected_version}" ]]; then
				echo "Attempt ${attempt}: version ${published_version} is published on WordPress.org."
				exit 0
			fi

			last_state="published version is ${published_version:-unknown}"
		else
			last_state="response was not a JSON object (HTTP ${http_code})"
		fi
	else
		# curl itself failed (DNS, connection, timeout, ...).
		# curl 自体が失敗した（名前解決・接続・タイムアウトなど）。
		last_state="request failed: ${response##*$'\n'}"
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
