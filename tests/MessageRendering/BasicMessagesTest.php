<?php

namespace Tests\MessageRendering;

use Masterminds\HTML5;
use Roundcube\Tests\ExitException;

/**
 * Test class to test simple messages.
 */
class BasicMessagesTest extends MessageRenderingTestCase
{
    /**
     * Test that two text mime-parts with disposition "attachment" are shown as
     * attachments.
     */
    public function testList00()
    {
        $domxpath = $this->runAndGetHtmlOutputDomxpath('99839b8ec12482419372f1edafa9de75@woodcrest.local');
        $this->assertSame('Lines', $this->getScrubbedSubject($domxpath));

        $bodyParts = $domxpath->query('//iframe[contains(@class, "framed-message-part")]');
        $this->assertCount(1, $bodyParts, 'Message body parts');

        $attchElems = $domxpath->query('//span[@class="attachment-name"]');
        $this->assertCount(2, $attchElems, 'Attachments');
        $this->assertStringStartsWith('lines.txt', $attchElems[0]->textContent);
        $this->assertStringStartsWith('lines_lf.txt', $attchElems[1]->textContent);

        $src = $bodyParts[0]->attributes->getNamedItem('src')->textContent;
        $url = parse_url($src, PHP_URL_QUERY);
        parse_str($url, $params);
        $this->assertSame('mail', $params['_task']);
        $this->assertSame('get', $params['_action']);
        $this->assertSame('INBOX', $params['_mbox']);
        $this->assertMatchesRegularExpression('/^\d+$/', $params['_uid']);
        $this->assertSame('1', $params['_part']);

        $domxpath_body = $this->runGetActionAndGetHtmlOutputDomxpath($params);
        $bodyElem = $domxpath_body->query('//body');
        $this->assertCount(1, $bodyElem, 'Message body');

        $this->assertStringStartsWith('Plain text message body.', trim($bodyElem[0]->textContent));
    }

    /**
     * Test that one inline image is not shown as attachment.
     */
    public function testList01()
    {
        $domxpath = $this->runAndGetHtmlOutputDomxpath('3ef8a0120cd7dc2fd776468c8515e29a@domain.tld');

        $this->assertSame('Test HTML with local and remote image', $this->getScrubbedSubject($domxpath));
        
        $bodyParts = $domxpath->query('//iframe[contains(@class, "framed-message-part")]');
        $this->assertCount(1, $bodyParts, 'Message body parts');
        
        $attchElems = $domxpath->query('//span[@class="attachment-name"]');
        $this->assertCount(0, $attchElems, 'Attachments');
        
        $src = $bodyParts[0]->attributes->getNamedItem('src')->textContent;
        $url = parse_url($src, PHP_URL_QUERY);
        parse_str($url, $params);
        $this->assertSame('mail', $params['_task']);
        $this->assertSame('get', $params['_action']);
        $this->assertSame('INBOX', $params['_mbox']);
        $this->assertMatchesRegularExpression('/^\d+$/', $params['_uid']);
        $this->assertSame('2.1', $params['_part']);

        // Get iframed content.
        $domxpath_body = $this->runGetActionAndGetHtmlOutputDomxpath($params);
        $bodyElem = $domxpath_body->query('//body');
        $this->assertCount(1, $bodyElem, 'Message body');
        $this->assertSame("Attached image: \nRemote image:", trim($bodyElem[0]->textContent));
    }

    /**
     * Test that text parts are shown and also listed as attachments, and that
     * filenames are properly listed.
     */
    public function testFilename()
    {
        $domxpath = $this->runAndGetHtmlOutputDomxpath('de75@tester.local');

        $this->assertSame('Attachment filename encoding', $this->getScrubbedSubject($domxpath));

        $bodyParts = $domxpath->query('//iframe[contains(@class, "framed-message-part")]');
        $this->assertCount(3, $bodyParts, 'Message body parts');

        $src = $bodyParts[0]->attributes->getNamedItem('src')->textContent;
        $this->assertStringContainsString('?_task=mail&_action=get&_mbox=INBOX&_uid=', $src);
        $this->assertStringContainsString('&_part=1', $src);

        $src = $bodyParts[1]->attributes->getNamedItem('src')->textContent;
        $this->assertStringContainsString('?_task=mail&_action=get&_mbox=INBOX&_uid=', $src);
        $this->assertStringContainsString('&_part=2', $src);

        $src = $bodyParts[2]->attributes->getNamedItem('src')->textContent;
        $this->assertStringContainsString('?_task=mail&_action=get&_mbox=INBOX&_uid=', $src);
        $this->assertStringContainsString('&_part=6', $src);

        $src = $bodyParts[0]->attributes->getNamedItem('src')->textContent;
        $url = parse_url($src, PHP_URL_QUERY);
        parse_str($url, $params);
        $this->assertSame('mail', $params['_task']);
        $this->assertSame('get', $params['_action']);
        $this->assertSame('INBOX', $params['_mbox']);
        $this->assertMatchesRegularExpression('/^\d+$/', $params['_uid']);
        $this->assertSame('1', $params['_part']);
        $domxpath_body = $this->runGetActionAndGetHtmlOutputDomxpath($params);
        $bodyElem = $domxpath_body->query('//body');
        $this->assertCount(1, $bodyElem, 'Message body');
        $this->assertSame("foo\nbar\ngna", trim($bodyElem[0]->textContent));

        $src = $bodyParts[1]->attributes->getNamedItem('src')->textContent;
        $url = parse_url($src, PHP_URL_QUERY);
        parse_str($url, $params);
        $this->assertSame('mail', $params['_task']);
        $this->assertSame('get', $params['_action']);
        $this->assertSame('INBOX', $params['_mbox']);
        $this->assertMatchesRegularExpression('/^\d+$/', $params['_uid']);
        $this->assertSame('2', $params['_part']);
        // TODO: This fails, but shouldn't – why?
        //$domxpath_body = $this->runGetActionAndGetHtmlOutputDomxpath($params);
        //$bodyElem = $domxpath_body->query('//body');
        //$this->assertCount(1, $bodyElem, 'Message body');
        //$this->assertSame('潦੯慢ੲ湧', trim($bodyElem[0]->textContent));

        $src = $bodyParts[2]->attributes->getNamedItem('src')->textContent;
        $url = parse_url($src, PHP_URL_QUERY);
        parse_str($url, $params);
        $this->assertSame('mail', $params['_task']);
        $this->assertSame('get', $params['_action']);
        $this->assertSame('INBOX', $params['_mbox']);
        $this->assertMatchesRegularExpression('/^\d+$/', $params['_uid']);
        $this->assertSame('6', $params['_part']);
        $domxpath_body = $this->runGetActionAndGetHtmlOutputDomxpath($params);
        $bodyElem = $domxpath_body->query('//body');
        $this->assertCount(1, $bodyElem, 'Message body');
        $this->assertSame("foo\nbar\ngna", trim($bodyElem[0]->textContent));


        $attchNames = $domxpath->query('//span[@class="attachment-name"]');
        $this->assertCount(6, $attchNames, 'Attachments');

        $this->assertSame('A011.txt', $attchNames[0]->textContent);
        $this->assertSame('A012.txt', $attchNames[1]->textContent);
        $this->assertSame('A014.txt', $attchNames[2]->textContent);
        $this->assertSame('żółć.png', $attchNames[3]->textContent);
        $this->assertSame('żółć.png', $attchNames[4]->textContent);
        $this->assertSame('very very very very long very very very very long ćććććć very very very long name.txt', $attchNames[5]->textContent);
    }
}
