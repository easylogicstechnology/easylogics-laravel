<?php

namespace Tests\WhatsApp;

use App\Services\WhatsApp\WhatsAppService as W;
use PHPUnit\Framework\TestCase;

/** The CakePHP WhatsApp bill rules, as ported to WhatsAppService (no database, no network). */
class WhatsAppServiceTest extends TestCase
{
    private function svc(string $bodyParams = 'member_name,flat_no,bill_no,amount,due_date'): W
    {
        return new W(['body_params' => $bodyParams, 'country_code' => '+91', 'template_name' => 'maintenance_bill_notification', 'language_code' => 'en']);
    }

    public function test_phone_validation_matches_cake(): void
    {
        $this->assertSame('No phone number', W::validatePhone('')['error']);
        $this->assertSame('No phone number', W::validatePhone(' - ')['error']);
        $this->assertSame('Phone number too short', W::validatePhone('12345')['error']);
        $this->assertSame('Landline number', W::validatePhone('02212345678')['error']);
        $this->assertSame('Non-Indian number', W::validatePhone('448208902649')['error']);
        $this->assertSame(['valid' => true, 'phone' => '918208902649'], W::validatePhone('82089 02649'));
        $this->assertSame(['valid' => true, 'phone' => '918208902649'], W::validatePhone('+91-8208902649'));
    }

    public function test_body_params_follow_config_order_and_are_safe(): void
    {
        $log = ['member_name' => "DINESH\t ", 'flat_no' => 'A-101', 'bill_no' => 1, 'amount' => 2000, 'bill_month' => '4', 'due_date' => '15/04/2026'];
        $this->assertSame(['DINESH', 'A-101', '1', '2,000.00', '15/04/2026'], $this->svc()->bodyParams($log, 'DEMO'));
        $this->assertCount(7, $this->svc('')->bodyParams($log, 'DEMO'));
        $this->assertSame('Mr. KISHOR B.  RANE', W::bodyParamSafe("Mr. KISHOR\tB.  RANE        \n"));
    }

    public function test_payload_strips_country_code_and_attaches_pdf(): void
    {
        $p = $this->svc()->payload('918208902649', ['a'], 'https://x/tmp/b.pdf', 7);
        $this->assertSame('8208902649', $p['phoneNumber']);
        $this->assertSame('+91', $p['countryCode']);
        $this->assertSame('maintenance_bill_notification', $p['template']['name']);
        $this->assertSame(['https://x/tmp/b.pdf'], $p['template']['headerValues']);
        $this->assertSame('Bill_7.pdf', $p['template']['fileName']);
        $this->assertArrayNotHasKey('headerValues', $this->svc()->payload('918208902649', ['a'])['template']);
    }

    public function test_batch_status_from_counts(): void
    {
        $c = W::countsFromStatuses(['SENT' => 1, 'INVALID_PHONE' => 7]);
        $this->assertSame(['COMPLETED', 1, 7], [$c['status'], $c['sent_count'], $c['invalid_phone_count']]);
        $this->assertSame('PARTIAL', W::countsFromStatuses(['SENT' => 2, 'API_LIMIT_REACHED' => 1])['status']);
        $this->assertSame('STOPPED', W::countsFromStatuses(['PENDING' => 3])['status']);
    }

    public function test_webhook_signature(): void
    {
        $this->assertTrue(W::signatureOk('{}', '', null));
        $this->assertTrue(W::signatureOk('{}', 's', ''));
        $this->assertTrue(W::signatureOk('{}', 's', 'sha256=' . hash_hmac('sha256', '{}', 's')));
        $this->assertFalse(W::signatureOk('{}', 's', 'sha256=bad'));
    }

    public function test_pdf_html_sanitizer_and_batch_id(): void
    {
        $this->assertSame('abc', W::sanitizeHtmlForPdf("a\x01b\xc2\x85c"));
        $this->assertMatchesRegularExpression('/^WA_104833_\d{8}_\d{6}_[0-9A-F]{4}$/', W::generateBatchId(104833));
    }

    public function test_meta_format_payload_auth_and_url(): void
    {
        $w = new W(['provider' => 'meta', 'api_url' => 'https://x.example/v3/{phone_number_id}/messages', 'phone_number_id' => '123', 'auth_header' => 'apikey', 'template_name' => 'bill', 'language_code' => 'en']);
        $this->assertSame('https://x.example/v3/123/messages', $w->endpoint());
        $this->assertSame(['apikey', 'K'], $w->authHeader('K'));
        $this->assertSame(['Authorization', 'Bearer K'], (new W(['provider' => 'meta']))->authHeader('K'));
        $this->assertSame(['Authorization', 'Basic K'], (new W(['provider' => 'interakt']))->authHeader('K'));

        $p = $w->metaPayload('918208902649', ['A', 'B'], 'https://x/tmp/b.pdf', 5);
        $this->assertSame('918208902649', $p['to']);
        $this->assertSame('bill', $p['template']['name']);
        $this->assertSame('document', $p['template']['components'][0]['parameters'][0]['type']);
        $this->assertSame('Bill_5.pdf', $p['template']['components'][0]['parameters'][0]['document']['filename']);
        $this->assertSame([['type' => 'text', 'text' => 'A'], ['type' => 'text', 'text' => 'B']], $p['template']['components'][1]['parameters']);
    }

    public function test_webhook_parsing_both_formats(): void
    {
        $i = W::parseWebhook(['type' => 'message_api_failed', 'data' => ['message' => ['id' => 'm1', 'message_status' => 'Failed', 'channel_failure_reason' => 'x', 'channel_error_code' => 131026]]]);
        $this->assertSame(['id' => 'm1', 'status' => 'FAILED', 'raw_status' => 'Failed', 'error' => 'x (code: 131026)'], $i[0]);
        $m = W::parseWebhook(['entry' => [['changes' => [['value' => ['statuses' => [['id' => 'wamid.1', 'status' => 'read'], ['id' => 'wamid.2', 'status' => 'failed', 'errors' => [['code' => 131049, 'title' => 'limit']]]]]]]]]]);
        $this->assertSame('READ', $m[0]['status']);
        $this->assertSame('limit (code: 131049)', $m[1]['error']);
        $this->assertSame([], W::parseWebhook(['foo' => 1]));
    }

    public function test_bill_month_name_for_k3_template(): void
    {
        $this->assertSame(['April 2026'], (new W(['body_params' => 'bill_month_name']))->bodyParams(['bill_month' => '4', 'bill_year' => '2026'], 'S'));
        $this->assertSame('March', W::monthName('3'));
        $this->assertSame('Apr-May', W::monthName('Apr-May'));
    }

    public function test_k3_provider_follows_k3_partners_api_v2(): void
    {
        $w = new W(['provider' => 'k3', 'wanumber' => '919323451048', 'template_name' => 'maintainanence_bill', 'language_code' => 'en']);
        $this->assertSame('https://partners.pinbot.ai/v2/messages', $w->endpoint());
        $this->assertSame(['apikey' => 'K', 'wanumber' => '919323451048'], $w->headers('K'));
        $this->assertTrue($w->isMetaFormat());
        $p = $w->metaPayload('918208902649', ['April 2026'], 'https://x/tmp/b.pdf', 1);
        $this->assertSame('whatsapp', $p['messaging_product']);
        $this->assertSame('maintainanence_bill', $p['template']['name']);
        $this->assertSame([['type' => 'text', 'text' => 'April 2026']], $p['template']['components'][1]['parameters']);
        // statuses forwarded without the Meta envelope, and the custom-header secret
        $this->assertSame('DELIVERED', W::parseWebhook(['statuses' => [['id' => 'w1', 'status' => 'delivered']]])[0]['status']);
        $this->assertTrue(W::signatureOk('{}', 's', null, 's'));
        $this->assertFalse(W::signatureOk('{}', 's', null, 'x'));
    }
}
