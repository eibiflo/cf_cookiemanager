<?php
// Build/Scripts/runTests.sh -s functional -p 8.1 -z debug -x -t 12 Tests/Functional/Updates/StaticDataUpdateWizardTest.php
declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Functional\Updates;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Updates\StaticDataUpdateWizard;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Test case
 *
 * @author Florian Eibisberger
 */
class StaticDataUpdateWizardTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/cf_cookiemanager',
    ];

    /**
     * @var CookieServiceRepository
     */
    private $cookieServiceRepository;

    /**
     * @var CookieCartegoriesRepository
     */
    private $cookieCategoriesRepository;

    /**
     * @var CookieFrontendRepository
     */
    private $cookieFrontendRepository;

    /**
     * @var CookieRepository|MockObject
     */
    private $cookieRepository;

    //TODO Create a Complete Test for the Update Wizard, without mocks to test if a Default installation works as expected with all Languages and Sites
    protected function setUp(): void
    {
        parent::setUp();
        $this->cookieServiceRepository =  GeneralUtility::makeInstance(CookieServiceRepository::class);
        $this->cookieCategoriesRepository =  GeneralUtility::makeInstance(CookieCartegoriesRepository::class);
        $this->cookieFrontendRepository = GeneralUtility::makeInstance(CookieFrontendRepository::class);
        $this->cookieRepository = $this->createMock(CookieRepository::class);
    }

    /**
     * @dataProvider siteConfigurationProvider
     * @test
     */
    public function testUpdateWizard(array $siteConfiguration, array $languageCodes): void
    {
        $siteIdentifier = $siteConfiguration['identifier'];
        GeneralUtility::makeInstance(SiteConfiguration::class)->write($siteIdentifier, $siteConfiguration);

        $subject = new StaticDataUpdateWizard(
            $this->cookieServiceRepository,
            $this->cookieCategoriesRepository,
            $this->cookieFrontendRepository,
            $this->cookieRepository
        );
        $subject->executeUpdate();

        //This tests if the Site Configuration is correct and the expected Languages are created, todo add more Tests for Services, Categories and Cookies
        foreach ($languageCodes as $langcode => $result){
            $frontendObject = $this->cookieFrontendRepository->getFrontendBySysLanguage($result["expectedLanguageId"], [$siteConfiguration['rootPageId']]);
            $this->assertEquals($result["expectedText"], $frontendObject[0]->getName());
            $this->assertEquals($result["expectedLanguageId"], $frontendObject[0]->_getProperty('_languageUid'));
            //$allCategories = $this->cookieCategoriesRepository->getAllCategories([$siteConfiguration['rootPageId']],$result["expectedLanguageId"]);
            //$externalMediaFound = $this->cookieCategoriesRepository->getCategoryByIdentifier("externalmedia",$result["expectedLanguageId"],[$siteConfiguration['rootPageId']]);
            //var_dump($externalMediaFound);
           // $this->assertEquals($result["expectedLanguageId"], $frontendObject[0]->getLanguageId()); //getLanguageId since v12
        }

    }

    /**
     *  SiteConfig Test Data Provider, for testing the update wizard
     * @return array
     */
    public static function siteConfigurationProvider(): array
    {
        return [
            //Setup Site Config 1 and test results
          [
                [
                    'identifier' => 'simpleMultiLang',
                    'rootPageId' => 1,
                    'base' => 'www.test.de',
                    'languages' => [
                        [
                            'title' => 'German',
                            'enabled' => true,
                            'languageId' => 0,
                            'base' => '/',
                            'locale' => 'de_DE.UTF-8',
                            'iso-639-1' => 'de', //only for TYPO3 11 and lower
                            'navigationTitle' => 'Deutsch',
                            'flag' => 'de',
                        ],
                        [
                            'title' => 'Austria',
                            'enabled' => true,
                            'languageId' => 1,
                            'base' => '/',
                            'locale' => 'de_AT.UTF-8',
                            'iso-639-1' => 'at', //only for TYPO3 11 and lower
                            'navigationTitle' => 'Deutsch',
                            'flag' => 'at',
                        ],
                        [
                            'title' => 'English',
                            'enabled' => true,
                            'languageId' => 2,
                            'base' => '/en',
                            'locale' => 'en_US.UTF-8',
                            'navigationTitle' => 'English',
                            'flag' => 'us',
                        ],

                    ],
                    'settings' => [
                        'debug' => 1,
                        'test' => true,
                    ],
                    'errorHandling' => [],
                    'routes' => [],
                ],
                //TEST Results
                [
                    "de" => [
                        "expectedText" => "Meine Website",
                        "expectedLanguageId" => 0,
                    ],
                    "at" => [
                        "expectedText" => "Meine Website",
                        "expectedLanguageId" => 1,
                    ],
                    "en" => [
                        "expectedText" => "My Website",
                        "expectedLanguageId" => 2,
                    ],


                ],
            ],
            [
                [
                    'identifier' => 'simpleMultiLang2',
                    'rootPageId' => 2,
                    'base' => 'www.test2.de',
                    'languages' => [
                        [
                            'title' => 'English',
                            'enabled' => true,
                            'languageId' => 0,
                            'base' => '/',
                            'locale' => 'en_US.UTF-8',
                            'navigationTitle' => 'English',
                            'flag' => 'us',
                        ],
                        [
                            'title' => 'German',
                            'enabled' => true,
                            'languageId' => 1,
                            'base' => '/en',
                            'locale' => 'de_DE.UTF-8',
                            'iso-639-1' => 'de', //only for TYPO3 11 and lower
                            'navigationTitle' => 'Deutsch',
                            'flag' => 'de',
                        ],
                    ],
                    'settings' => [
                        'debug' => 1,
                        'test' => true,
                    ],
                    'errorHandling' => [],
                    'routes' => [],
                ],
                   //TEST Results
                   [
                       "en" => [
                           "expectedText" => "My Website",
                           "expectedLanguageId" => 0,
                       ],
                       "de" => [
                           "expectedText" => "Meine Website",
                           "expectedLanguageId" => 1,
                       ],
                   ],
            ],


        ];
    }

}
