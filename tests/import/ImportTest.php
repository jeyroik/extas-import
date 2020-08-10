<?php
namespace tests\import;

use Dotenv\Dotenv;
use extas\components\console\TSnuffConsole;
use extas\components\extensions\ExtensionRepository;
use extas\components\packages\CrawlerExtas;
use extas\components\packages\Initializer;
use extas\components\packages\PackageImport;
use extas\components\plugins\Plugin;
use extas\components\plugins\PluginEmpty;
use extas\components\plugins\PluginException;
use extas\components\plugins\export\PluginExportByField;
use extas\components\plugins\PluginImport;
use extas\components\plugins\PluginRepository;
use extas\components\repositories\TSnuffRepository;
use extas\interfaces\extensions\IExtension;
use extas\interfaces\IHasIO;
use extas\interfaces\packages\IPackageImport;
use extas\interfaces\plugins\IPlugin;
use extas\interfaces\samples\parameters\ISampleParameter;
use extas\interfaces\stages\IStagePackageExportBuild;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportTest
 *
 * @package tests\import
 * @author jeyroik <jeyroik@gmail.com>
 */
class ImportTest extends TestCase
{
    use TSnuffRepository;
    use TSnuffConsole;

    protected function setUp(): void
    {
        parent::setUp();
        $env = Dotenv::create(getcwd() . '/tests/');
        $env->load();
        $this->registerSnuffRepos([
            'pluginRepo' => PluginRepository::class,
            'extRepo' => ExtensionRepository::class
        ]);
    }

    /**
     * Clean up
     */
    public function tearDown(): void
    {
        $this->unregisterSnuffRepos();
    }

    public function testValid()
    {
        $output = $this->getOutput(true);
        $packages = [
            $this->getImportPackage(
                IPackageImport::ON_MISS__CONTINUE,
                IPackageImport::ON_MISS__CONTINUE
            ),
            $this->getExportPackage()
        ];
        $generatedData = [];

        $this->selfInstall($output);

        $plugin = new PluginImport([
            IHasIO::FIELD__INPUT => $this->getInput(),
            IHasIO::FIELD__OUTPUT => $output
        ]);
        $plugin($packages, $generatedData);

        $plugins = $this->allSnuffRepos('pluginRepo');
        $this->assertCount(
            6,
            $plugins,
            'Not enough plugins: ' . '###' . PHP_EOL . $output->fetch()
        );
    }

    public function testMissedImport()
    {
        $output = $this->getOutput(true);
        $packages = [$this->getExportPackage()];
        $generatedData = [];

        $this->selfInstall($output);

        $plugin = new PluginImport([
            IHasIO::FIELD__INPUT => $this->getInput(),
            IHasIO::FIELD__OUTPUT => $output
        ]);
        $plugin($packages, $generatedData);

        $plugins = $this->allSnuffRepos('pluginRepo');
        $this->assertCount(4, $plugins, 'Not enough plugins: ' . '###');
    }

    public function testMissedExportPackageContinue()
    {
        $output = $this->getOutput(true);
        $packages = [
            $this->getImportPackage(
                IPackageImport::ON_MISS__CONTINUE,
                IPackageImport::ON_MISS__CONTINUE
            ),
        ];
        $generatedData = [];

        $this->selfInstall($output);

        $plugin = new PluginImport([
            IHasIO::FIELD__INPUT => $this->getInput(),
            IHasIO::FIELD__OUTPUT => $output
        ]);
        $plugin($packages, $generatedData);

        $plugins = $this->allSnuffRepos('pluginRepo');
        $this->assertCount(4, $plugins, 'Not enough plugins: ' . '###');
    }

    public function testMissedExportPackageThrowAnError()
    {
        $output = $this->getOutput(true);
        $packages = [
            $this->getImportPackage(
                IPackageImport::ON_MISS__THROW_AN_ERROR,
                IPackageImport::ON_MISS__CONTINUE
            )
        ];
        $generatedData = [];

        $this->selfInstall($output);

        $plugin = new PluginImport([
            IHasIO::FIELD__INPUT => $this->getInput(),
            IHasIO::FIELD__OUTPUT => $output
        ]);
        $this->expectExceptionMessage(
            'Missed or unknown package "test-export" for export or section "' . PluginImport::SECTION__EXPORT . '"'
        );
        $plugin($packages, $generatedData);
    }

    public function testMissedExportSectionContinue()
    {
        $output = $this->getOutput(true);
        $packages = [
            $this->getImportPackage(
                IPackageImport::ON_MISS__CONTINUE,
                IPackageImport::ON_MISS__CONTINUE
            ),
            $this->getExportPackage(['plugins'])
        ];
        $generatedData = [];

        $this->selfInstall($output);

        $plugin = new PluginImport([
            IHasIO::FIELD__INPUT => $this->getInput(),
            IHasIO::FIELD__OUTPUT => $output
        ]);
        $plugin($packages, $generatedData);

        $plugins = $this->allSnuffRepos('pluginRepo');
        $this->assertCount(6, $plugins, 'Not enough plugins: ' . '###');

        $extensions = $this->allSnuffRepos('extRepo');
        $this->assertCount(
            1, // ExtensionRepositoryGet (see setUp() for details)
            $extensions,
            'Too much extensions: ' . print_r($extensions, true)
        );
    }

    public function testMissedExportSectionThrowAnError()
    {
        $output = $this->getOutput(true);
        $packages = [
            $this->getImportPackage(
                IPackageImport::ON_MISS__THROW_AN_ERROR,
                IPackageImport::ON_MISS__THROW_AN_ERROR
            ),
            $this->getExportPackage(['plugins'])
        ];
        $generatedData = [];

        $this->selfInstall($output);

        $plugin = new PluginImport([
            IHasIO::FIELD__INPUT => $this->getInput(),
            IHasIO::FIELD__OUTPUT => $output
        ]);
        $this->expectExceptionMessage(
            'Missed or unknown section "extensions" for export in the "test-export"'
        );
        $plugin($packages, $generatedData);
    }

    public function testExportWildcard()
    {
        $output = $this->getOutput(true);
        $packages = [
            $this->getImportPackage(
                IPackageImport::ON_MISS__CONTINUE,
                IPackageImport::ON_MISS__CONTINUE,
                true
            ),
            $this->getExportPackage()
        ];
        $generatedData = [];

        $this->selfInstall($output);

        $plugin = new PluginImport([
            IHasIO::FIELD__INPUT => $this->getInput(),
            IHasIO::FIELD__OUTPUT => $output
        ]);
        $plugin($packages, $generatedData);

        $plugins = $this->allSnuffRepos('pluginRepo');
        // 2 - PluginExportByField, 1 - PluginImport, 1 - PluginExportByWildcard +3 PluginEmpty from export
        $this->assertCount(7, $plugins, 'Not enough plugins: ' . '###');
    }

    public function testValidByGeneralPlugin()
    {
        $output = $this->getOutput(true);
        $packages = [
            $this->getImportPackage(
                IPackageImport::ON_MISS__CONTINUE,
                IPackageImport::ON_MISS__CONTINUE
            ),
            $this->getExportPackage()
        ];
        $generatedData = [];

        $this->createWithSnuffRepo('pluginRepo', new Plugin([
            Plugin::FIELD__CLASS => PluginExportByField::class,
            Plugin::FIELD__STAGE => IStagePackageExportBuild::NAME,
            Plugin::FIELD__PARAMETERS => [
                'field' => [
                    ISampleParameter::FIELD__NAME => 'field',
                    ISampleParameter::FIELD__VALUE => 'class'
                ]
            ]
        ]));

        $plugin = new PluginImport([
            IHasIO::FIELD__INPUT => $this->getInput(),
            IHasIO::FIELD__OUTPUT => $output
        ]);
        $plugin($packages, $generatedData);

        $plugins = $this->allSnuffRepos('pluginRepo');
        // 1 - PluginExportByField +2 PluginEmpty from export
        $this->assertCount(3, $plugins, 'Not enough plugins: ' . '###');
    }

    protected function selfInstall(OutputInterface  $output): void
    {
        $installer = new Initializer([
            Initializer::FIELD__INPUT => $this->getInput(),
            Initializer::FIELD__OUTPUT => $output
        ]);
        $installer->run([
            'extas/import' => json_decode(file_get_contents(getcwd() . '/extas.json'), true)
        ]);
    }

    /**
     * @param string $onMissPackage
     * @param string $onMissSection
     * @param bool $allPlugins
     * @return array
     */
    protected function getImportPackage(string $onMissPackage, string $onMissSection, bool $allPlugins = false): array
    {
        $plugins = $allPlugins ? '*' : PluginEmpty::class;

        return [
            'name' => 'test-import',
            CrawlerExtas::FIELD__WORKING_DIRECTORY => '/test-import/',
            PluginImport::SECTION__IMPORT => [
                PackageImport::FIELD__PARAMETERS => [
                    PackageImport::PARAM__ON_MISS_PACKAGE => [
                        ISampleParameter::FIELD__NAME => PackageImport::PARAM__ON_MISS_PACKAGE,
                        ISampleParameter::FIELD__VALUE => $onMissPackage
                    ],
                    PackageImport::PARAM__ON_MISS_SECTION => [
                        ISampleParameter::FIELD__NAME => PackageImport::PARAM__ON_MISS_SECTION,
                        ISampleParameter::FIELD__VALUE => $onMissSection
                    ]
                ],
                PackageImport::FIELD__FROM => [
                    'test-export' => [
                        'plugins' => $plugins,
                        'extensions' => [PluginEmpty::class]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param string[] $sections
     * @return array
     */
    protected function getExportPackage($sections = ['plugins', 'extensions']): array
    {
        $bySection = [
            'plugins' => [
                [
                    IPlugin::FIELD__CLASS => PluginEmpty::class,
                    IPlugin::FIELD__STAGE => 'stage1',
                    'install_on' => 'install'
                ],
                [
                    IPlugin::FIELD__CLASS => PluginEmpty::class,
                    IPlugin::FIELD__STAGE => 'stage2',
                    'install_on' => 'install'
                ],
                [
                    IPlugin::FIELD__CLASS => PluginException::class,
                    IPlugin::FIELD__STAGE => 'stage2',
                    'install_on' => 'install'
                ]
            ],
            'extensions' => [
                [
                    IExtension::FIELD__CLASS => PluginEmpty::class,
                    IExtension::FIELD__SUBJECT => 'subject1',
                    IExtension::FIELD__METHODS => [],
                    'install_on' => 'install'
                ],
                [
                    IExtension::FIELD__CLASS => PluginEmpty::class,
                    IExtension::FIELD__SUBJECT => 'subject2',
                    IExtension::FIELD__METHODS => [],
                    'install_on' => 'install'
                ],
                [
                    IExtension::FIELD__CLASS => PluginException::class,
                    IExtension::FIELD__SUBJECT => 'subject1',
                    IExtension::FIELD__METHODS => [],
                    'install_on' => 'install'
                ]
            ]
        ];

        $export = [];

        foreach ($sections as $name) {
            $export[$name] = $bySection[$name];
        }

        return [
            'name' => 'test-export',
            CrawlerExtas::FIELD__WORKING_DIRECTORY => '/vendor/test-export/',
            PluginImport::SECTION__EXPORT => $export
        ];
    }
}
