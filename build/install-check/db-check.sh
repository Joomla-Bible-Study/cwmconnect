#!/usr/bin/env bash
#
# Read-only checks of a package install on a cwm-site-create test site: the
# extension rows, the admin menu, the schema version, Joomla's own schema check,
# and every file the manifests say the installer puts in place. The expectations
# come from the manifests and SQL update files, so they follow the code.
#
# Usage: build/install-check/db-check.sh <site-id>
#   <site-id>  a site recorded in build.properties (cwm-site-create does that)
#
# Exits 1 when a check fails.

set -u

ROOT=$(cd "$(dirname "$0")/../.." && pwd)
ID=${1:?usage: $0 <site-id>}

prop() {
    grep -E "^builder\.${ID}\.$1[[:space:]]*=" "$ROOT/build.properties" | head -1 | sed -E 's/^[^=]*=[[:space:]]*//'
}

SITE=$(prop path)

if [ -z "$SITE" ] || [ ! -f "$SITE/configuration.php" ]; then
    echo "No Joomla site recorded for \"$ID\" in build.properties."
    exit 2
fi

cd "$SITE" || exit 2
PREFIX=$(grep -o "dbprefix = '[^']*'" configuration.php | sed "s/dbprefix = '//; s/'//")
FAILED=0

q() { ddev mysql -N -B -e "$(printf '%s' "$1" | sed "s/#__/${PREFIX}/g")" 2>&1; }
ok() { echo "  ok    $*"; }
fail() { echo "  FAIL  $*"; FAILED=1; }

echo "== Extensions ($SITE)"
q "SELECT CONCAT_WS('  ', type, IF(element = '', '<empty element>', element), IFNULL(NULLIF(folder, ''), '-'),
          CONCAT('enabled=', enabled), CONCAT('package_id=', package_id))
   FROM #__extensions
   WHERE element IN ('pkg_cwmconnect', 'com_cwmconnect', 'mpdf', 'mod_birthdayanniversary')
      OR (type = 'plugin' AND (element = 'cwmconnect' OR name LIKE 'plg\\_%cwmconnect'))
   ORDER BY FIELD(type, 'package', 'component', 'library', 'module', 'plugin'), folder" | sed 's/^/        /'

# Component, library and module, plus one row per plugin folder in the repository.
WANT=$(( $(find "$ROOT/plugins" -mindepth 2 -maxdepth 2 -type d -name cwmconnect | wc -l) + 3 ))
GOT=$(q "SELECT COUNT(*) FROM #__extensions c JOIN #__extensions p ON p.extension_id = c.package_id
         WHERE p.element = 'pkg_cwmconnect' AND c.element <> ''")

if [ "$GOT" = "$WANT" ]; then ok "the package owns $GOT named children"; else fail "the package owns $GOT named children, expected $WANT"; fi

EMPTY=$(q "SELECT COUNT(*) FROM #__extensions WHERE type = 'plugin' AND element = '' AND name LIKE 'plg\\_%cwmconnect'")

if [ "$EMPTY" = "0" ]; then ok "no plugin installed without an element"; else fail "$EMPTY plugin(s) installed with an empty element"; fi

# The plugin states a fresh install leaves: the groups build/script.install.php
# lists in ENABLE_ON_INSTALL switched on, every other bundled plugin off. On a
# site where an admin has changed them since, these fail by design.
ON=" $(grep -o "ENABLE_ON_INSTALL = \[[^]]*\]" "$ROOT/build/script.install.php" | grep -o "'[a-z]*'" | tr -d "'" | tr '\n' ' ')"

for dir in "$ROOT"/plugins/*/cwmconnect; do
    group=$(basename "$(dirname "$dir")")
    case $ON in *" $group "*) want=1 ;; *) want=0 ;; esac
    got=$(q "SELECT enabled FROM #__extensions WHERE type = 'plugin' AND element = 'cwmconnect' AND folder = '$group'")

    if [ "$got" = "$want" ]; then ok "$group plugin enabled=$got"; else fail "$group plugin enabled=${got:-<missing>}, a fresh install sets $want"; fi
done

echo "== Admin menu"
WANT=$(php -r 'echo count(simplexml_load_string(file_get_contents($argv[1]))->administration->submenu->menu);' "$ROOT/cwmconnect.xml")
GOT=$(q "SELECT COUNT(*) FROM #__menu WHERE client_id = 1 AND menutype = 'main' AND level = 2 AND link LIKE '%com_cwmconnect%'")

if [ "$GOT" = "$WANT" ]; then ok "$GOT submenu entries"; else fail "$GOT submenu entries, the manifest has $WANT"; fi

echo "== Schema"
WANT=$(php -r '$v = array_map(fn ($f) => basename($f, ".sql"), glob($argv[1] . "/admin/sql/updates/mysql/*.sql")); usort($v, "version_compare"); echo end($v);' "$ROOT")
GOT=$(q "SELECT s.version_id FROM #__schemas s JOIN #__extensions e ON e.extension_id = s.extension_id WHERE e.element = 'com_cwmconnect'")

if [ "$GOT" = "$WANT" ]; then ok "schema version $GOT"; else fail "schema version \"$GOT\", the newest update file is $WANT"; fi

if ddev exec php cli/joomla.php maintenance:database 2>&1 | grep -q 'All database table structures are up to date'; then
    ok "maintenance:database reports no problems"
else
    fail "maintenance:database reports problems; run it inside the container to see them"
fi

echo "== Installed files"
# Every <files> and <media> entry of every manifest, mapped to where Joomla installs it.
PATHS=$(php -r '
    $root = $argv[1];
    $load = fn (string $f) => simplexml_load_string(file_get_contents($f));
    $list = function ($section, string $dest): void {
        foreach ($section->children() as $entry) {
            echo $dest, "/", trim((string) $entry), "\n";
        }
    };
    $com = $load("$root/cwmconnect.xml");
    $list($com->files, "components/com_cwmconnect");
    $list($com->administration->files, "administrator/components/com_cwmconnect");
    $list($com->media, "media/" . $com->media["destination"]);
    foreach (glob("$root/plugins/*/cwmconnect/cwmconnect.xml") as $f) {
        $x = $load($f);
        $list($x->files, "plugins/" . $x["group"] . "/cwmconnect");
    }
    foreach (glob("$root/modules/site/*/*.xml") as $f) {
        $list($load($f)->files, "modules/" . basename(dirname($f)));
    }
    $list($load("$root/libraries/mpdf/mpdf.xml")->files, "libraries/mpdf");
    echo "media/com_cwmconnect/photos/.htaccess\n";
' "$ROOT")
MISSING=0

while read -r path; do
    if [ ! -e "$path" ]; then
        fail "$path is missing"
        MISSING=$((MISSING + 1))
    fi
done <<EOF
$PATHS
EOF

if [ "$MISSING" = "0" ]; then ok "all $(printf '%s\n' "$PATHS" | grep -c .) installed paths present"; fi

exit $FAILED
