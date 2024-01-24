<?php
namespace CodingFreaks\CfCookiemanager\Tests\Functional;

use CodingFreaks\CfCookiemanager\Middleware\ModifyHtmlContent;
use CodingFreaks\CfCookiemanager\Utility\RenderUtility;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MiddleWareHtmlContentReplaceTest extends UnitTestCase
{
    private $renderUtility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderUtility =  $this->mockRenderUtilityWithClassifyContentMock();
    }

    /**
     * @test
     */
    private function mockRenderUtilityWithClassifyContentMock(): RenderUtility
    {
        // Mock EventDispatcherInterface
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // Create RenderUtility instance (partial mock)
        $renderUtility = $this->getMockBuilder(RenderUtility::class)
            ->setConstructorArgs([$eventDispatcher])
            ->onlyMethods(['classifyContent']) // Specify the method to mock
            ->getMock();

        // Set up the mock behavior for classifyContent method
        $renderUtility
            ->method('classifyContent')
            ->willReturn('service123');

        return $renderUtility;
    }

    /**
     * internal helper function to create a HTML Svgs
     */
    public function createSvgImage()
    {
        //This is invalid HTML works.. but invalid dose not work with DOM Parser
        $svg = '<?xml version="1.0" encoding="UTF-8"><svg id="Ebene_2" viewBox="0 0 748.77 102.09"><defs><style>.cls-1{fill:#f7f6e8;}.cls-2{fill:#025d53;}.cls-3{fill:#9fc131;}.cls-4{fill:#d2d929;}</style></defs><g id="Ebene_1-2"><path class="cls-1" d="m12.83,32.06v24.49c0,8.33,3.43,11.95,12.05,11.95h28.21v12.83H20.08c-13.62,0-20.08-8.03-20.08-22.53v-29.19C0,15.11,6.37,7.28,20.08,7.28h33.01v12.83h-28.31c-8.42,0-11.95,3.62-11.95,11.95Z"/><path class="cls-1" d="m58.45,49.39c0-14.5,7.93-23.51,21.16-23.51h11.75c13.22,0,21.16,9.01,21.16,23.51v9.4c0,14.5-7.93,23.51-21.16,23.51h-11.75c-13.22,0-21.16-9.01-21.16-23.51v-9.4Zm30.07-11.56h-5.1c-9.3,0-13.32,3.04-13.32,11.07v10.38c0,8.03,4.01,11.07,13.32,11.07h5.1c8.52,0,12.34-3.04,12.34-11.07v-10.38c0-8.03-3.82-11.07-12.34-11.07Z"/><path class="cls-1" d="m169.32,81.33h-10.68l-1.28-10.77h-1.96c-.78,6.07-3.62,11.46-11.95,11.46l-8.91-.1c-9.99,0-16.16-7.45-16.16-21.35v-12.93c0-13.42,6.17-20.86,17.93-20.86h20.37V5.12h12.64v76.21Zm-30.17-11.76l7.15.1c6.56.1,10.38-1.17,10.38-9.99v-21.35l-17.63-.1c-5.49,0-8.72,2.55-8.72,8.03v15.28c0,5.48,3.33,7.93,8.82,8.03Z"/><path class="cls-1" d="m179.58,5.12h12.54v12.34h-12.54V5.12Zm12.44,21.74v54.46h-12.34V26.86h12.34Z"/><path class="cls-1" d="m212.36,26.86v12.15h1.67c1.96-7.35,5.48-12.44,15.38-12.44h4.11c11.86,0,17.54,5.97,17.54,17.53v37.22h-12.05v-31.64c0-7.74-3.33-11.07-10.19-11.07-8.23,0-14.89,1.27-14.89,10.09v32.62h-12.25V26.86h10.68Z"/><path class="cls-1" d="m259.35,74.47c0-6.07,3.13-10.67,9.99-10.67h2.15v-.98c-10.38-1.47-14.5-5.97-14.5-15.77v-3.72c0-12.64,4.8-17.44,17.44-17.44h8.72c6.86,0,11.07,2.06,11.07,7.74h1.67l-.1-13.91h10.68v28.6c0,11.95-6.08,17.63-17.24,17.63h-14.5c-2.64,0-3.82,1.67-3.82,3.92,0,2.45,1.17,3.53,3.92,3.53l21.25.1c7.45,0,10.68,2.94,10.68,9.79v7.93c0,7.64-3.23,10.87-10.68,10.87h-36.05v-10.38h33.11c2.55,0,3.92-1.27,3.92-4.01,0-3.04-1.37-4.31-3.92-4.31h-23.8c-6.86,0-9.99-2.84-9.99-8.91Zm15.57-19.49h13.13c4.21,0,6.27-2.06,6.27-5.87v-6.37c0-3.82-2.06-5.88-6.27-5.88h-13.13c-3.82,0-5.78,2.06-5.78,5.88v6.37c0,3.82,1.96,5.87,5.78,5.87Z"/><path class="cls-1" d="m374.86,19.72h-38.2v20.37h30.86v12.44h-30.86v28.8h-12.93V7.28h51.13v12.44Z"/><path class="cls-1" d="m392.77,26.86v12.15h1.66c2.16-7.93,4.9-12.83,14.6-12.83h7.15v12.83h-11.17c-7.35,0-10.58,2.94-10.58,9.99v32.32h-12.34V26.86h10.68Z"/><path class="cls-1" d="m417.52,59.19v-10.48c0-13.81,8.82-22.82,22.43-22.82h8.62c12.93,0,21.06,6.17,21.06,22.43v11.36h-40.16c0,7.35,3.23,10.29,10.97,10.29h24.29v11.65h-27.52c-12.25,0-19.69-8.72-19.69-22.43Zm40.07-9.11v-2.16c0-7.05-3.23-10.58-10.39-10.58h-7.15c-6.76,0-9.99,3.53-9.99,10.58l-.1,2.45,27.63-.29Z"/><path class="cls-1" d="m504.96,38.13h-27.23v-11.95h29.38c10.19,0,15.67,6.17,15.67,16.36v38.79h-10.19l-.1-11.07h-1.67c-1.76,6.76-6.56,11.46-15.18,11.46h-5.38c-10.19,0-15.67-5.48-15.67-15.67v-4.9c0-8.23,4.5-12.74,12.73-12.74h23.51v-4.21c0-4.01-2.06-6.07-5.88-6.07Zm-13.22,32.13h9.99c4.31,0,8.91-.88,9.21-9.01v-2.35h-19.2c-3.82,0-5.88,2.06-5.88,5.68s2.06,5.68,5.88,5.68Z"/><path class="cls-1" d="m532.08,5.12h12.24v40.55h7.74l14.5-18.81h14.2l-19.1,23.8,21.94,30.66h-14.01l-16.65-23.51h-8.62v23.51h-12.24V5.12Z"/><path class="cls-1" d="m606.16,26.86h26.74v11.76h-26.25c-4.41,0-5.58,1.08-5.58,3.53s.68,3.92,5.78,4.7l17.43,2.74c6.47.98,11.95,3.53,11.95,12.73v3.62c0,10.19-5.48,15.38-15.87,15.38h-27.82v-11.76h26.35c5.58,0,6.76-1.07,6.76-4.21s-.88-4.21-6.47-5.2l-15.96-2.74c-7.25-1.28-12.74-3.53-12.74-14.5v-3.63c0-6.95,5.48-12.44,15.67-12.44Z"/><path class="cls-4" d="m734.28,68.69h14.5l-6.17,23.8h-11.27l2.94-23.8Zm-2.06-41.83h14.59v14.59h-14.59v-14.59Z"/><path class="cls-2" d="m665.11,59.38v12.36c0,6.18,4.75,9.38,12.25,9.38l-.11,11.37c-9.49,0-23.62-6.62-23.62-14.46,0-7.06,1.88-25.72-4.3-25.72h-6.18v-12.69h6.18c6.18,0,4.3-18.1,4.3-25.16,0-8.39,14.13-14.46,23.62-14.46l.11,11.48c-7.5,0-12.25,3.2-12.25,9.27v15.34c0,6.51-5.74,8.61-11.37,8.61l-.11,2.43c3.75.11,7.4,1.11,9.38,3.53,2.1,2.76,2.1,5.41,2.1,8.72Z"/><path class="cls-3" d="m703.21,50.66c1.99-2.43,5.63-3.42,9.27-3.53v-2.43c-5.63,0-11.37-2.1-11.37-8.61v-15.34c0-6.07-4.75-9.27-12.25-9.27l.11-11.48c9.38,0,23.62,6.07,23.62,14.46,0,7.06-1.87,25.16,4.2,25.16h6.29v12.69h-6.29c-6.07,0-4.2,18.65-4.2,25.72,0,7.84-14.24,14.46-23.62,14.46l-.11-11.37c7.5,0,12.25-3.2,12.25-9.38v-12.36c0-3.31,0-5.96,2.09-8.72Z"/></g></svg>';
        $svg .= '<?xml version="1.0" encoding="UTF-8"?><svg id="Ebene_2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 748.77 102.09"><defs><style>.cls-1{fill:#f7f6e8;}.cls-2{fill:#025d53;}.cls-3{fill:#9fc131;}.cls-4{fill:#d2d929;}</style></defs><g id="Ebene_1-2"><path class="cls-1" d="m12.83,32.06v24.49c0,8.33,3.43,11.95,12.05,11.95h28.21v12.83H20.08c-13.62,0-20.08-8.03-20.08-22.53v-29.19C0,15.11,6.37,7.28,20.08,7.28h33.01v12.83h-28.31c-8.42,0-11.95,3.62-11.95,11.95Z"/><path class="cls-1" d="m58.45,49.39c0-14.5,7.93-23.51,21.16-23.51h11.75c13.22,0,21.16,9.01,21.16,23.51v9.4c0,14.5-7.93,23.51-21.16,23.51h-11.75c-13.22,0-21.16-9.01-21.16-23.51v-9.4Zm30.07-11.56h-5.1c-9.3,0-13.32,3.04-13.32,11.07v10.38c0,8.03,4.01,11.07,13.32,11.07h5.1c8.52,0,12.34-3.04,12.34-11.07v-10.38c0-8.03-3.82-11.07-12.34-11.07Z"/><path class="cls-1" d="m169.32,81.33h-10.68l-1.28-10.77h-1.96c-.78,6.07-3.62,11.46-11.95,11.46l-8.91-.1c-9.99,0-16.16-7.45-16.16-21.35v-12.93c0-13.42,6.17-20.86,17.93-20.86h20.37V5.12h12.64v76.21Zm-30.17-11.76l7.15.1c6.56.1,10.38-1.17,10.38-9.99v-21.35l-17.63-.1c-5.49,0-8.72,2.55-8.72,8.03v15.28c0,5.48,3.33,7.93,8.82,8.03Z"/><path class="cls-1" d="m179.58,5.12h12.54v12.34h-12.54V5.12Zm12.44,21.74v54.46h-12.34V26.86h12.34Z"/><path class="cls-1" d="m212.36,26.86v12.15h1.67c1.96-7.35,5.48-12.44,15.38-12.44h4.11c11.86,0,17.54,5.97,17.54,17.53v37.22h-12.05v-31.64c0-7.74-3.33-11.07-10.19-11.07-8.23,0-14.89,1.27-14.89,10.09v32.62h-12.25V26.86h10.68Z"/><path class="cls-1" d="m259.35,74.47c0-6.07,3.13-10.67,9.99-10.67h2.15v-.98c-10.38-1.47-14.5-5.97-14.5-15.77v-3.72c0-12.64,4.8-17.44,17.44-17.44h8.72c6.86,0,11.07,2.06,11.07,7.74h1.67l-.1-13.91h10.68v28.6c0,11.95-6.08,17.63-17.24,17.63h-14.5c-2.64,0-3.82,1.67-3.82,3.92,0,2.45,1.17,3.53,3.92,3.53l21.25.1c7.45,0,10.68,2.94,10.68,9.79v7.93c0,7.64-3.23,10.87-10.68,10.87h-36.05v-10.38h33.11c2.55,0,3.92-1.27,3.92-4.01,0-3.04-1.37-4.31-3.92-4.31h-23.8c-6.86,0-9.99-2.84-9.99-8.91Zm15.57-19.49h13.13c4.21,0,6.27-2.06,6.27-5.87v-6.37c0-3.82-2.06-5.88-6.27-5.88h-13.13c-3.82,0-5.78,2.06-5.78,5.88v6.37c0,3.82,1.96,5.87,5.78,5.87Z"/><path class="cls-1" d="m374.86,19.72h-38.2v20.37h30.86v12.44h-30.86v28.8h-12.93V7.28h51.13v12.44Z"/><path class="cls-1" d="m392.77,26.86v12.15h1.66c2.16-7.93,4.9-12.83,14.6-12.83h7.15v12.83h-11.17c-7.35,0-10.58,2.94-10.58,9.99v32.32h-12.34V26.86h10.68Z"/><path class="cls-1" d="m417.52,59.19v-10.48c0-13.81,8.82-22.82,22.43-22.82h8.62c12.93,0,21.06,6.17,21.06,22.43v11.36h-40.16c0,7.35,3.23,10.29,10.97,10.29h24.29v11.65h-27.52c-12.25,0-19.69-8.72-19.69-22.43Zm40.07-9.11v-2.16c0-7.05-3.23-10.58-10.39-10.58h-7.15c-6.76,0-9.99,3.53-9.99,10.58l-.1,2.45,27.63-.29Z"/><path class="cls-1" d="m504.96,38.13h-27.23v-11.95h29.38c10.19,0,15.67,6.17,15.67,16.36v38.79h-10.19l-.1-11.07h-1.67c-1.76,6.76-6.56,11.46-15.18,11.46h-5.38c-10.19,0-15.67-5.48-15.67-15.67v-4.9c0-8.23,4.5-12.74,12.73-12.74h23.51v-4.21c0-4.01-2.06-6.07-5.88-6.07Zm-13.22,32.13h9.99c4.31,0,8.91-.88,9.21-9.01v-2.35h-19.2c-3.82,0-5.88,2.06-5.88,5.68s2.06,5.68,5.88,5.68Z"/><path class="cls-1" d="m532.08,5.12h12.24v40.55h7.74l14.5-18.81h14.2l-19.1,23.8,21.94,30.66h-14.01l-16.65-23.51h-8.62v23.51h-12.24V5.12Z"/><path class="cls-1" d="m606.16,26.86h26.74v11.76h-26.25c-4.41,0-5.58,1.08-5.58,3.53s.68,3.92,5.78,4.7l17.43,2.74c6.47.98,11.95,3.53,11.95,12.73v3.62c0,10.19-5.48,15.38-15.87,15.38h-27.82v-11.76h26.35c5.58,0,6.76-1.07,6.76-4.21s-.88-4.21-6.47-5.2l-15.96-2.74c-7.25-1.28-12.74-3.53-12.74-14.5v-3.63c0-6.95,5.48-12.44,15.67-12.44Z"/><path class="cls-4" d="m734.28,68.69h14.5l-6.17,23.8h-11.27l2.94-23.8Zm-2.06-41.83h14.59v14.59h-14.59v-14.59Z"/><path class="cls-2" d="m665.11,59.38v12.36c0,6.18,4.75,9.38,12.25,9.38l-.11,11.37c-9.49,0-23.62-6.62-23.62-14.46,0-7.06,1.88-25.72-4.3-25.72h-6.18v-12.69h6.18c6.18,0,4.3-18.1,4.3-25.16,0-8.39,14.13-14.46,23.62-14.46l.11,11.48c-7.5,0-12.25,3.2-12.25,9.27v15.34c0,6.51-5.74,8.61-11.37,8.61l-.11,2.43c3.75.11,7.4,1.11,9.38,3.53,2.1,2.76,2.1,5.41,2.1,8.72Z"/><path class="cls-3" d="m703.21,50.66c1.99-2.43,5.63-3.42,9.27-3.53v-2.43c-5.63,0-11.37-2.1-11.37-8.61v-15.34c0-6.07-4.75-9.27-12.25-9.27l.11-11.48c9.38,0,23.62,6.07,23.62,14.46,0,7.06-1.87,25.16,4.2,25.16h6.29v12.69h-6.29c-6.07,0-4.2,18.65-4.2,25.72,0,7.84-14.24,14.46-23.62,14.46l-.11-11.37c7.5,0,12.25-3.2,12.25-9.38v-12.36c0-3.31,0-5.96,2.09-8.72Z"/></g></svg>';
        return $svg;
    }

    /**
     * internal helper function to create a HTML image map
     */
    public function createHtmlImageMap()
    {
        $html = '
        <img src="workplace.jpg" alt="Workplace" usemap="#workmap">
        <map name="workmap">
          <area shape="rect" coords="34,44,270,350" alt="Computer" href="computer.htm">
          <area shape="rect" coords="290,172,333,250" alt="Phone" href="phone.htm">
          <area shape="circle" coords="337,300,44" alt="Coffee" href="coffee.htm">
        </map>
            ';
        return $html;
    }

    /**
     * internal helper function to create a HTML image map
     */
    public function createScripts(){
        $html = '
 <p>
 &lt;script src="example.com" &gt;
    console.log("testwowie");
  &lt;/script&gt;
</p>

 <script   
   src="example.com" 
   > console.log("wowiemowie");</script>
   
<script
  src="example.com"
  type="text/javascript"
>
  console.log("wowiemowie");
</script>

<script
  async
  src="example.com"
  defer
>
  console.log("wowiemowie");
</script>


<script src="example.com"  type="text/javascript">console.log("wowiemowie");</script>

<script
  src="example.com"
  type="text/javascript"
>
  console.log("wowiemowie");
</script>

<script
  src=\'example.com\'
  type="text/javascript"
>
  console.log("wowiemowie");
</script>

<script>
  var script1 = document.createElement(\'script\');
  script1.type = \'text/javascript\';
  script1.async = true;
  script1.src = \'https://www.googletagmanager.com/gtag/js?id=[##GT_TRACKING_ID##]\';
  script1.setAttribute(\'data-cookiecategory\', \'analytics\');

  var script2 = document.createElement("script");
  script2.type = "text/javascript";
  script2.setAttribute("data-cookiecategory", "analytics");
  script2.innerHTML = `
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag(\'js\', new Date());
    console.log("Header");
    gtag(\'config\', \'[##GT_TRACKING_ID##]\');
  `;

  script1.onload = function() {
      document.head.appendChild(script2);
  };

  document.head.appendChild(script1);
</script>


                                      <script              src="example.com"                  > console.log("wowiemowie");</script>
        ';

        return $html;
    }

    /**
     * Interal helper function to generate a random HTML5 structure.
     */
    private function generateRandomHTML5Structure() {
        $html = '<!DOCTYPE html>\n';
        $html .= '<html>\n';
        $html .= '<head>\n';
        $html .= '<meta charset="UTF-8">\n';
        $html .= '<title>Random HTML5 Structure</title>\n';
        $html .= '<!-- This is a comment -->\n';
        $html .= '<script type="text/javascript">\n';
        $html .= '// This is a script comment with special chars 马克思-*ÜD*Ä \n';
        $html .= '</script>\n';
        $html .= '<link rel="stylesheet" type="text/css" href="styles.css">\n';
        $html .= '</head>\n';
        $html .= '<body>\n';
        $html .= '<h1>This is a random HTML5 structure</h1>\n';
        $html .= '<iframe allow=" accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen; " src="https://www.youtube.com/embed/UV0mhY2Dxr0?si=AjLgLCl2xtnxR5Yg"></iframe>\n';
        $html .=  $this->createSvgImage();
        $html .=  $this->createScripts();
        $html .= '<p>ÄÖÜ#*´ß</p>\n';
        $html .= '<p>Markus Kuhn -- 2012-04-11</p>\n';
        $html .= '<p>This is an example of a plain-text file encoded in UTF-8.</p>\n';
        $html .= '<p>Danish (da) --------- Quizdeltagerne spiste jordbær med fløde, mens cirkusklovnen Wolther spillede på xylofon.</p>\n';
        $html .= '<p>German (de) ----------- Falsches Üben von Xylophonmusik quält jeden größeren Zwerg</p>\n';
        $html .= '<p>English (en) ------------ The quick brown fox jumps over the lazy dog</p>\n';
        $html .= '<p>Spanish (es) ------------ El pingüino Wenceslao hizo kilómetros bajo exhaustiva lluvia y frío, añoraba a su querido cachorro.</p>\n';
        $html .= '<p>Chinese (zh) ------------ 马克思 -- 2012-04-11</p>\n';
        $html .= '<p>Russian (ru) ------------ В чащах юга жил бы цитрус? Да, но фальшивый экземпляр!</p>\n';
        $html .= '<p>Japanese (ja) ------------ 私の車はどこですか</p>\n';
        $html .= '<p>Arabic (ar) -------------- هل تتكلم العربية</p>\n';
        $html .= '<p>Hebrew (he) -------------- האם אתה מדבר עברית</p>\n';
        $html .= '<p>Thai (th) ---------------- คุณพูดภาษาไทยได้ไหม</p>\n';
        $html .=  $this->createHtmlImageMap();
        $html .= '</body>\n';
        $html .= '</html>\n';

        return $html;
    }

    /**
     * @test
     */
    public function testMiddlewareHookDefault()
    {
        // Arrange
        $html = $this->generateRandomHTML5Structure();

        // Act
        $endResult = $this->renderUtility->cfHook($html,["scriptBlocking" => 0,'scriptReplaceByRegex' => 1]);

        $this->assertStringContainsString('<!DOCTYPE html>', $endResult);
        $this->assertStringContainsString('马克思', $endResult);
        $this->assertStringContainsString('чащах', $endResult);
        $this->assertStringContainsString('Да', $endResult);
        $this->assertStringContainsString('kilómetros', $endResult);
        $this->assertStringContainsString('fløde', $endResult);
        $this->assertStringContainsString('ÄÖÜ', $endResult);
        $this->assertStringContainsString('אתה', $endResult);
        $this->assertStringContainsString('العربية', $endResult);
        $this->assertStringContainsString('私の車はどこですか', $endResult);
        $this->assertStringNotContainsString('<iframe', $endResult); // iframe should be removed with service123
        $this->assertStringContainsString('data-service="service123"', $endResult); //iframe replaced by data-service div handeld in Browser js
        $this->assertStringContainsString('<html>', $endResult);
        $this->assertStringContainsString('<body>', $endResult);
        $this->assertStringContainsString('</body>', $endResult);
        $this->assertStringContainsString('</html>', $endResult);
        $this->assertStringContainsString($this->createHtmlImageMap(), $endResult);
        $this->assertStringContainsString($this->createSvgImage(), $endResult);
        $this->assertStringContainsString('<script type="text/javascript">\n// This is a script comment with special chars 马克思-*ÜD*Ä \n</script>\n', $endResult);
        $this->assertStringContainsString('<script              src="example.com"                   type="text/plain" data-service="service123"> console.log("wowiemowie");</script>', $endResult);
        $this->assertStringContainsString('&lt;script src="example.com" &gt;', $endResult);
        $this->assertStringContainsString('&lt;/script&gt;', $endResult);
        $this->assertStringContainsString('<script>
  var script1 = document.createElement(\'script\');
  script1.type = \'text/javascript\';
  script1.async = true;
  script1.src = \'https://www.googletagmanager.com/gtag/js?id=[##GT_TRACKING_ID##]\';
  script1.setAttribute(\'data-cookiecategory\', \'analytics\');

  var script2 = document.createElement("script");
  script2.type = "text/javascript";
  script2.setAttribute("data-cookiecategory", "analytics");
  script2.innerHTML = `
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag(\'js\', new Date());
    console.log("Header");
    gtag(\'config\', \'[##GT_TRACKING_ID##]\');
  `;

  script1.onload = function() {
      document.head.appendChild(script2);
  };

  document.head.appendChild(script1);', $endResult);
    }
}