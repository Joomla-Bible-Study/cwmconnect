#!/usr/bin/env bash
#
# HTTP smoke test of a package install on a cwm-site-create test site. Logs in to
# the administrator and fetches every submenu view the component manifest lists,
# plus Options and the new-member form; checks that guests meet the members-only
# wall; then logs in to the site and fetches the member views. A page fails on an
# HTTP error, a Joomla error page, a PHP warning, a danger alert or an untranslated
# language key.
#
# PHP warnings only reach the page with error reporting at maximum, so set that on
# the test site first:
#   ddev exec php cli/joomla.php config:set error_reporting=maximum
#
# Usage: build/install-check/smoke.sh <site-id>
#   <site-id>  a site recorded in build.properties (cwm-site-create does that)
#
# Credentials come from build.properties and are never printed. Exits 1 when a
# page fails.

set -u

ROOT=$(cd "$(dirname "$0")/../.." && pwd)
ID=${1:?usage: $0 <site-id>}

prop() {
    grep -E "^builder\.${ID}\.$1[[:space:]]*=" "$ROOT/build.properties" | head -1 | sed -E 's/^[^=]*=[[:space:]]*//'
}

BASE=$(prop url)
BASE=${BASE%/}
USER_NAME=$(prop admin_user)
PASSWORD=$(prop admin_pass)

if [ -z "$BASE" ] || [ -z "$USER_NAME" ] || [ -z "$PASSWORD" ]; then
    echo "No url and admin credentials recorded for \"$ID\" in build.properties."
    exit 2
fi

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT
FAILED=0

# Everything a page shows that it should not, one line each.
scan() {
    php -r '
        $html = (string) file_get_contents($argv[1]);
        $text = preg_replace("#<(script|style)\b[^>]*>.*?</\1>#s", "", $html);
        $flat = fn (string $s): string => trim(preg_replace("/\s+/", " ", strip_tags($s)));
        $issues = [];

        if (preg_match("#<blockquote[^>]*>(.*?)</blockquote>#s", $text, $m)) {
            $issues[] = "error page: " . $flat($m[1]);
        }

        preg_match_all("#<b>(Warning|Deprecated|Notice|Fatal error)</b>:(.*?)<br#s", $text, $hits, PREG_SET_ORDER);

        foreach ($hits as $hit) {
            $issues[] = "PHP " . $hit[1] . ": " . $flat($hit[2]);
        }

        preg_match_all("#<joomla-alert type=\"(?:danger|error)\".*?</joomla-alert>#s", $text, $alerts);

        foreach ($alerts[0] as $alert) {
            $issues[] = "alert: " . $flat($alert);
        }

        if (preg_match_all("/\b(?:COM_CWMCONNECT|PLG_[A-Z]+_CWMCONNECT|MOD_BIRTHDAYANNIVERSARY)_[A-Z0-9_]+\b/", $text, $keys)) {
            $issues[] = "untranslated: " . implode(", ", array_unique($keys[0]));
        }

        if (preg_match("#<title>[^<]*\b(com_cwmconnect_[a-z0-9_]+)#i", $html, $m)) {
            $issues[] = "untranslated title: " . $m[1];
        }

        foreach (array_unique($issues) as $issue) {
            echo substr($issue, 0, 220), "\n";
        }
    ' "$1"
}

# Fetch one page and report it. Args: label, cookie jar, url, then curl options.
page() {
    local label=$1 jar=$2 url=$3
    shift 3
    local out="$WORK/page.html" code issues
    code=$(curl -sk -b "$jar" -c "$jar" -o "$out" -w '%{http_code}' "$@" "$url")
    issues=$(scan "$out")

    if [ "$code" -ge 500 ] || [ "$code" = "000" ] || [ -n "$issues" ]; then
        printf '  FAIL  %s  %s\n' "$code" "$label"
        [ -n "$issues" ] && printf '%s\n' "$issues" | sed 's/^/          /'
        FAILED=1
    else
        printf '  ok    %s  %s\n' "$code" "$label"
    fi
}

# Log in through a Joomla login form. Args: cookie jar, login page url, user field,
# password field, extra form fields. The site form carries its task in its action
# URL, and a posted task would override it, so only the administrator sends one.
login() {
    local jar=$1 form="$WORK/login.html" action token return
    curl -sk -L -b "$jar" -c "$jar" -o "$form" "$2"
    action=$(grep -o -E '<form [^>]*>' "$form" | grep -i 'login' | head -1 | sed -E 's/.*action="([^"]*)".*/\1/; s/&amp;/\&/g')
    token=$(grep -o -E 'name="[0-9a-f]{32}" value="1"' "$form" | head -1 | cut -d'"' -f2)
    return=$(grep -o -E 'name="return" value="[^"]*"' "$form" | head -1 | cut -d'"' -f4)
    [ -n "$token" ] || return 1
    case $action in http*) ;; *) action="$BASE$action" ;; esac
    curl -sk -b "$jar" -c "$jar" -o /dev/null --data-urlencode "$3=$USER_NAME" --data-urlencode "$4=$PASSWORD" \
        --data "${5:+$5&}return=${return}&${token}=1" "$action"
}

echo "== Administrator ($BASE)"
ADMIN="$WORK/admin.jar"

if ! login "$ADMIN" "$BASE/administrator/index.php" username passwd "option=com_login&task=login" \
    || curl -sk -b "$ADMIN" "$BASE/administrator/index.php" | grep -q 'name="passwd"'; then
    echo "  FAIL  could not log in to the administrator"
    exit 1
fi

LINKS=$(php -r '
    foreach (simplexml_load_string(file_get_contents($argv[1]))->administration->submenu->menu as $menu) {
        echo $menu["link"], "\n";
    }
' "$ROOT/cwmconnect.xml")

while read -r link; do
    page "$link" "$ADMIN" "$BASE/administrator/index.php?$link"
done <<EOF
$LINKS
option=com_config&view=component&component=com_cwmconnect
EOF

page "option=com_cwmconnect&task=member.add (the edit form)" "$ADMIN" "$BASE/administrator/index.php?option=com_cwmconnect&task=member.add" -L

echo "== Guest"
for view in members households; do
    target=$(curl -sk -o /dev/null -w '%{http_code} %{redirect_url}' "$BASE/index.php?option=com_cwmconnect&view=$view")

    case $target in
        3*login*) printf '  ok    %s  view=%s goes to the login wall\n' "${target%% *}" "$view" ;;
        *) printf '  FAIL  %s  view=%s is not walled off from guests\n' "$target" "$view"; FAILED=1 ;;
    esac
done

page "view=redeem (public by design)" "$WORK/guest.jar" "$BASE/index.php?option=com_cwmconnect&view=redeem" -L

echo "== Member"
SITE_JAR="$WORK/site.jar"

if ! login "$SITE_JAR" "$BASE/index.php?option=com_users&view=login" username password \
    || curl -sk -L -b "$SITE_JAR" "$BASE/index.php?option=com_users&view=profile" | grep -q 'name="password"'; then
    echo "  FAIL  could not log in to the site"
    exit 1
fi

for view in members households myprofile; do
    page "view=$view" "$SITE_JAR" "$BASE/index.php?option=com_cwmconnect&view=$view" -L
done

exit $FAILED
