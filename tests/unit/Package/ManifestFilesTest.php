<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Package;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Joomla's installer copies only what an extension's manifest lists, whatever else
 * its zip carries. The dev installs can't show a gap: `composer link` symlinks whole
 * folders, and their plugins were registered through Discover, which names a plugin
 * after its folder. These tests hold every manifest the package builds against the
 * tracked files it is built from.
 */
#[CoversNothing]
final class ManifestFilesTest extends TestCase
{
    /**
     * Tracked entries that sit beside an extension's files on purpose and are never
     * installed, keyed by the repository folder they are in.
     */
    private const array NOT_INSTALLED = [
        // The recipe the build runs to generate vendor/; nothing reads it at runtime.
        'libraries/mpdf' => ['composer.json'],
    ];

    /**
     * Listed entries the build generates, so a clean checkout doesn't have them.
     */
    private const array BUILD_OUTPUT = [
        'libraries/mpdf' => ['vendor'],
    ];

    /**
     * @var array<string, string>|null  manifest path => repository folder of its zip root
     */
    private static ?array $manifests = null;

    /**
     * @var list<string>|null  every tracked path, from `git ls-files`
     */
    private static ?array $tracked = null;

    protected function setUp(): void
    {
        if (self::tracked() === []) {
            self::markTestSkipped('Needs a git checkout: the manifests are checked against tracked files.');
        }
    }

    /**
     * Each `<files>` and `<media>` section of every manifest the package builds.
     *
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function sections(): iterable
    {
        foreach (self::manifests() as $manifest => $zipRoot) {
            $sections = self::load($manifest)->xpath('/extension/files | /extension/media | /extension/administration/files');

            foreach ($sections ?: [] as $section) {
                $listed = [];

                foreach ($section->children() as $entry) {
                    $listed[] = trim((string) $entry);
                }

                $name = \sprintf('%s <%s folder="%s">', $manifest, $section->getName(), (string) $section['folder']);

                yield $name => [$manifest, self::inRepo($zipRoot, (string) $section['folder']), $listed];
            }
        }
    }

    /**
     * Every language file a manifest names, as a repository path.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function languageFiles(): iterable
    {
        foreach (self::manifests() as $manifest => $zipRoot) {
            $sections = self::load($manifest)->xpath('/extension/languages | /extension/administration/languages');

            foreach ($sections ?: [] as $section) {
                $folder = self::inRepo($zipRoot, (string) $section['folder']);

                foreach ($section->children() as $language) {
                    $path = self::inRepo($folder, trim((string) $language));

                    yield $manifest . ' ' . $path => [$manifest, $path];
                }
            }
        }
    }

    /**
     * Every plugin and module manifest, with the element its folder says it should get.
     *
     * @return iterable<string, array{string, string, string}>
     */
    public static function namedExtensions(): iterable
    {
        foreach (array_keys(self::manifests()) as $manifest) {
            $type = (string) self::load($manifest)['type'];

            if ($type === 'plugin' || $type === 'module') {
                yield $manifest => [$manifest, $type, basename(\dirname($manifest))];
            }
        }
    }

    /**
     * @param  list<string>  $listed
     */
    #[Test]
    #[DataProvider('sections')]
    public function everyTrackedEntryIsInstalled(string $manifest, string $folder, array $listed): void
    {
        $installed = [...$listed, ...self::languageFolders($manifest, $folder), ...(self::NOT_INSTALLED[$folder] ?? [])];

        if (\dirname($manifest) === $folder) {
            $installed[] = basename($manifest);
        }

        $missing = array_values(array_diff(self::trackedEntries($folder), $installed));

        self::assertSame([], $missing, \sprintf(
            '%s never installs %s from %s/: Joomla copies only what the manifest lists.',
            $manifest,
            implode(', ', $missing),
            $folder
        ));
    }

    /**
     * @param  list<string>  $listed
     */
    #[Test]
    #[DataProvider('sections')]
    public function everyListedEntryExists(string $manifest, string $folder, array $listed): void
    {
        $absent = array_values(array_diff($listed, self::trackedEntries($folder), self::BUILD_OUTPUT[$folder] ?? []));

        self::assertSame([], $absent, \sprintf(
            '%s lists %s, which is not in %s/; the install would abort.',
            $manifest,
            implode(', ', $absent),
            $folder
        ));
    }

    #[Test]
    #[DataProvider('languageFiles')]
    public function everyLanguageFileExists(string $manifest, string $path): void
    {
        self::assertContains($path, self::tracked(), $manifest . ' lists a language file that is not in the repository.');
    }

    /**
     * A plugin's element comes only from a `<files>` child carrying `plugin="…"`
     * (PluginAdapter::getElement() in Joomla 5 and 6). Without one the plugin installs
     * with an empty element and its files land loose in the group folder. A module may
     * name itself with `<element>` instead.
     */
    #[Test]
    #[DataProvider('namedExtensions')]
    public function theInstallerCanNameTheExtension(string $manifest, string $type, string $element): void
    {
        $xml   = self::load($manifest);
        $names = [];

        foreach ($xml->files->children() as $entry) {
            if ((string) $entry[$type] !== '') {
                $names[] = (string) $entry[$type];
            }
        }

        if ($type === 'module' && (string) $xml->element !== '') {
            $names[] = (string) $xml->element;
        }

        self::assertSame([$element], array_values(array_unique($names)), \sprintf(
            '%s must name its %s "%s" exactly once, on a <files> child (%s="%s").',
            $manifest,
            $type,
            $element,
            $type,
            $element
        ));
    }

    /**
     * Every manifest the package builds, mapped to the repository folder its zip root
     * is built from. Read from cwm-build.config.json, so a child extension added there
     * is covered here without touching this test.
     *
     * @return array<string, string>
     */
    private static function manifests(): array
    {
        if (self::$manifests !== null) {
            return self::$manifests;
        }

        $config = json_decode((string) file_get_contents(self::root() . '/cwm-build.config.json'), true, 512, JSON_THROW_ON_ERROR);
        $builds = [$config['build']];

        foreach ($config['package']['includes'] as $include) {
            if (isset($include['config'])) {
                $builds[] = $include['config'];
            }
        }

        self::$manifests = [];

        foreach ($builds as $build) {
            $roots = [];

            foreach ($build['sources'] as $source) {
                $from = trim($source['from'], '/');
                $to   = trim($source['to'] ?? '', '/');

                // Each zip copies repository folders unchanged below a single offset,
                // which is what lets a manifest's folder attributes map back here.
                if ($to !== '' && !str_ends_with($from, $to)) {
                    throw new \UnexpectedValueException(\sprintf('%s maps %s to %s; teach this test that layout.', $build['manifest'], $from, $to));
                }

                $roots[] = $to === '' ? $from : rtrim(substr($from, 0, -\strlen($to)), '/');
            }

            $roots = array_values(array_unique($roots));

            if (\count($roots) !== 1) {
                throw new \UnexpectedValueException(\sprintf('%s is built from more than one root: %s.', $build['manifest'], implode(', ', $roots)));
            }

            self::$manifests[$build['manifest']] = $roots[0];
        }

        return self::$manifests;
    }

    /**
     * Top-level entries of $folder holding a file one of the manifest's `<languages>`
     * sections installs. A section may name its folder in a `folder` attribute or in
     * each `<language>` path, so the check goes by the file's full path.
     *
     * @return list<string>
     */
    private static function languageFolders(string $manifest, string $folder): array
    {
        $covered  = [];
        $sections = self::load($manifest)->xpath('/extension/languages | /extension/administration/languages');

        foreach ($sections ?: [] as $section) {
            $base = self::inRepo(self::manifests()[$manifest], (string) $section['folder']);

            foreach ($section->children() as $language) {
                $path = self::inRepo($base, trim((string) $language));

                if (str_starts_with($path, $folder . '/')) {
                    $covered[] = explode('/', substr($path, \strlen($folder) + 1))[0];
                }
            }
        }

        return array_values(array_unique($covered));
    }

    /**
     * The first path segment of every tracked file under $folder.
     *
     * @return list<string>
     */
    private static function trackedEntries(string $folder): array
    {
        $prefix  = $folder === '' ? '' : $folder . '/';
        $entries = [];

        foreach (self::tracked() as $path) {
            if (str_starts_with($path, $prefix)) {
                $entries[] = explode('/', substr($path, \strlen($prefix)))[0];
            }
        }

        return array_values(array_unique($entries));
    }

    /**
     * @return list<string>
     */
    private static function tracked(): array
    {
        if (self::$tracked === null) {
            $output        = shell_exec('git -C ' . escapeshellarg(self::root()) . ' ls-files -z 2>/dev/null');
            self::$tracked = \is_string($output) ? array_values(array_filter(explode("\0", $output))) : [];
        }

        return self::$tracked;
    }

    private static function load(string $manifest): \SimpleXMLElement
    {
        $xml = simplexml_load_string((string) file_get_contents(self::root() . '/' . $manifest));

        if ($xml === false) {
            throw new \UnexpectedValueException($manifest . ' is not well-formed XML.');
        }

        return $xml;
    }

    private static function inRepo(string $base, string $path): string
    {
        return trim($base . '/' . trim($path, '/'), '/');
    }

    private static function root(): string
    {
        return \dirname(__DIR__, 3);
    }
}
