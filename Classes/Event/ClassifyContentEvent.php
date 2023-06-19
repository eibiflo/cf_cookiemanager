<?php
namespace CodingFreaks\CfCookiemanager\Event;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**

ClassifyContentEvent

This event is triggered when content needs to be classified by searching for iframes and scripts to retrieve URLs and find the service.
The event provides methods to get the provider URL and set/get the service identifier.
 */
final class ClassifyContentEvent
{
    private $providerURL;
    private $serviceIdentifier;

    /**

    ClassifyContentEvent constructor.
    @param string $providerURL The URL of the content provider
     */
    public function __construct(string $providerURL)
    {
        $this->providerURL = $providerURL;
    }
    /**

    Get the provider URL.
    @return string The URL of the content provider
     */
    public function getProviderURL(): string
    {
        return $this->providerURL;
    }
    /**

    Set the service identifier.
    @param string $serviceIdentifier The identifier of the service
     */
    public function setServiceIdentifier(string $serviceIdentifier): void
    {
        $this->serviceIdentifier = $serviceIdentifier;
    }
    /**

    Get the service identifier.
    @return string|null The identifier of the service, or null if not set
     */
    public function getServiceIdentifier(): ?string
    {
        return $this->serviceIdentifier;
    }
}