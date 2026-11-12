<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Member Identities "Download / Upload Member Opening Balance" with field selection:
 * the download holds only the ticked columns and the upload updates only the columns present
 * in the file. Needs a scratch copy of the CakePHP database (database name must contain
 * "scratch"); the members it touches are restored afterwards. Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=mu_scratch vendor/bin/phpunit tests/Feature/MemberOpeningBalanceFieldsTest.php
 */
class MemberOpeningBalanceFieldsTest extends TestCase
{
    private int $society;
    private array $snapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }

        $row = DB::table('members')->where('status', 1)->select('society_id', DB::raw('count(*) c'))
            ->groupBy('society_id')->having('c', '>=', 2)->orderBy('c')->first();
        if (!$row || !User::find($row->society_id)) {
            $this->markTestSkipped('No society with members in this database.');
        }
        $this->society = (int) $row->society_id;
        $this->snapshot = DB::table('members')->where('society_id', $this->society)->get()
            ->map(fn ($m) => (array) $m)->all();
    }

    protected function tearDown(): void
    {
        foreach ($this->snapshot as $m) {
            $id = $m['id'];
            unset($m['id']);
            DB::table('members')->where('id', $id)->update($m);
        }
        parent::tearDown();
    }

    private function asSociety()
    {
        return $this->actingAs(User::findOrFail($this->society));
    }

    public function test_download_contains_only_ticked_fields(): void
    {
        $res = $this->asSociety()->get(route('society.downloadMemberOpeningBalance', ['fields' => ['mobile', 'area']]));
        $res->assertOk();
        $csv = $res->streamedContent();
        $header = str_getcsv(strtok($csv, "\n"));

        $this->assertSame(['Member ID', 'Member (Reference)', 'Flat/Shop No (Reference)', 'Building Name', 'Wing Name (Reference)', 'Mobile No', 'Area'], $header);
    }

    public function test_download_without_selection_has_every_field(): void
    {
        $csv = $this->asSociety()->get(route('society.downloadMemberOpeningBalance'))->streamedContent();
        $header = str_getcsv(strtok($csv, "\n"));

        $this->assertSame(['Member Name', 'Flat/Shop No', 'Building Name', 'Wing Name'], array_slice($header, 1, 4) === [] ? [] : [$header[1], $header[2], $header[3], $header[4]]);
        $this->assertSame(['Mobile No', 'Email Address', 'Area', 'Opening Principal', 'Opening Interest', 'Opening Tax', 'Opening Bill Date', 'Opening Bill Due Date'], array_slice($header, 5));
    }

    public function test_upload_updates_only_the_columns_in_the_file(): void
    {
        $member = DB::table('members')->where('society_id', $this->society)->where('status', 1)->orderBy('id')->first();
        $before = (array) $member;

        $csv = "Member ID,Member Name,Flat/Shop No,Building Name,Wing Name,Email Address,Opening Principal\n"
            . "{$member->id},x,x,x,x,new.mail@example.com,1234.50\n";
        $path = tempnam(sys_get_temp_dir(), 'mob');
        file_put_contents($path, $csv);

        $this->asSociety()->post(route('society.uploadMemberOpeningBalance'), [
            'member_csv' => new UploadedFile($path, 'ob.csv', 'text/csv', null, true),
        ])->assertRedirect(route('society.memberIdentity'));

        $after = (array) DB::table('members')->where('id', $member->id)->first();
        $this->assertSame('new.mail@example.com', $after['member_email']);
        $this->assertEquals(1234.50, (float) $after['op_principal']);
        // untouched columns
        $this->assertSame($before['member_phone'], $after['member_phone']);
        $this->assertSame($before['area'], $after['area']);
        $this->assertSame($before['op_interest'], $after['op_interest']);
        $this->assertSame($before['op_tax'], $after['op_tax']);
    }

    public function test_member_name_is_downloaded_and_updated_only_when_ticked(): void
    {
        $csv = $this->asSociety()->get(route('society.downloadMemberOpeningBalance', ['fields' => ['name']]))->streamedContent();
        $this->assertSame(['Member ID', 'Member Name', 'Flat/Shop No (Reference)', 'Building Name', 'Wing Name (Reference)'], str_getcsv(strtok($csv, "\n")));

        $member = DB::table('members')->where('society_id', $this->society)->where('status', 1)->orderBy('id')->first();
        $path = tempnam(sys_get_temp_dir(), 'mob');
        file_put_contents($path, "Member ID,Member Name\n{$member->id},ZZ TEST NAME\n");
        $this->asSociety()->post(route('society.uploadMemberOpeningBalance'), ['member_csv' => new UploadedFile($path, 'ob.csv', 'text/csv', null, true)]);

        $after = DB::table('members')->where('id', $member->id)->first();
        $this->assertSame('ZZ TEST NAME', $after->member_name);
        $this->assertSame($member->member_phone, $after->member_phone);
    }

    public function test_flat_wing_and_bill_dates_can_be_downloaded_and_updated(): void
    {
        $csv = $this->asSociety()->get(route('society.downloadMemberOpeningBalance', ['fields' => ['flat', 'op_bill_date', 'op_bill_due_date']]))->streamedContent();
        $this->assertSame(
            ['Member ID', 'Member (Reference)', 'Flat/Shop No', 'Building Name', 'Wing Name (Reference)', 'Opening Bill Date', 'Opening Bill Due Date'],
            str_getcsv(strtok($csv, "\n"))
        );

        $member = DB::table('members')->where('society_id', $this->society)->where('status', 1)->orderBy('id')->first();
        $path = tempnam(sys_get_temp_dir(), 'mob');
        file_put_contents($path, "Member ID,Flat/Shop No,Wing Name,Opening Bill Date,Opening Bill Due Date\n{$member->id},ZZ-99,No Such Wing,01-04-2026,15/04/2026\n");
        $this->asSociety()->post(route('society.uploadMemberOpeningBalance'), ['member_csv' => new UploadedFile($path, 'ob.csv', 'text/csv', null, true)]);

        $after = DB::table('members')->where('id', $member->id)->first();
        $this->assertSame('ZZ-99', $after->flat_no);
        $this->assertSame('2026-04-01', substr((string) $after->op_bill_date, 0, 10));
        $this->assertSame('2026-04-15', substr((string) $after->op_bill_due_date, 0, 10));
        $this->assertSame($member->wing_id, $after->wing_id, 'an unknown wing name must leave the wing unchanged');
    }

    public function test_upload_cannot_touch_another_societys_member(): void
    {
        $other = DB::table('members')->where('society_id', '!=', $this->society)->first();
        if (!$other) {
            $this->markTestSkipped('Only one society in this database.');
        }
        $otherBefore = (array) $other;

        $path = tempnam(sys_get_temp_dir(), 'mob');
        file_put_contents($path, "Member ID,Mobile No\n{$other->id},9999999999\n");
        $this->asSociety()->post(route('society.uploadMemberOpeningBalance'), [
            'member_csv' => new UploadedFile($path, 'ob.csv', 'text/csv', null, true),
        ]);

        $this->assertSame($otherBefore['member_phone'], DB::table('members')->where('id', $other->id)->value('member_phone'));
    }
}
