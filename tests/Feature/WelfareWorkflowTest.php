<?php

namespace Tests\Feature;

use App\Exports\AnnualBenefitsChartExport;
use App\Exports\AnnualDuesChartExport;
use App\Models\Benefit;
use App\Models\BenefitRequest;
use App\Models\BenefitType;
use App\Models\DuesPayment;
use App\Models\DuesPaymentDeletionRequest;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\Staff;
use App\Models\User;
use App\Services\DuesCalculationService;
use App\Services\ExcelImportService;
use App\Services\StaffExcelImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Carbon\Carbon;

class WelfareWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_log_in(): void
    {
        $this->post(route('login.store'), [
            'login' => 'admin',
            'password' => 'ChangeMe123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_restricted_administrator_only_accesses_assigned_modules(): void
    {
        $administrator = User::factory()->create([
            'name' => 'Dues Administrator',
            'username' => 'dues-admin',
            'status' => 'active',
        ]);
        $administrator->assignRole(Role::findByName('Administrator'));
        $administrator->syncPermissions([Permission::findByName('manage dues')]);

        $this->actingAs($administrator)->get(route('admin.dues.record'))->assertOk();
        $this->actingAs($administrator)->get(route('admin.staff.index'))->assertForbidden();
        $this->actingAs($administrator)->get(route('admin.administrators.index'))->assertForbidden();
    }

    public function test_authorized_admin_can_create_an_admin_with_selected_options(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.administrators.store'), [
            'name' => 'Reports Officer',
            'username' => 'reports-officer',
            'email' => 'reports@example.test',
            'status' => 'active',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'permissions' => ['view reports', 'view dashboard'],
        ])->assertSessionHasNoErrors();

        $created = User::query()->where('username', 'reports-officer')->firstOrFail();
        $this->assertTrue($created->hasRole('Administrator'));
        $this->assertTrue($created->hasDirectPermission('view reports'));
        $this->assertFalse($created->can('manage staff'));
    }

    public function test_staff_can_log_in(): void
    {
        $staff = $this->createStaffWithUser();

        $this->post(route('login.store'), [
            'login' => $staff->user->username,
            'password' => 'password',
        ])->assertRedirect(route('staff.dashboard'));
    }

    public function test_login_uses_application_name_from_settings(): void
    {
        \App\Models\Setting::query()->updateOrCreate(['key' => 'application_name'], ['value' => 'Adisadel Welfare Portal', 'type' => 'string']);

        $this->get(route('login'))->assertOk()->assertSee('Adisadel Welfare Portal');
    }

    public function test_staff_can_edit_profile_but_cannot_change_staff_id(): void
    {
        $staff = $this->createStaffWithUser('LOCKED01', 'Original Name');

        $this->actingAs($staff->user)->put(route('staff.profile.update'), [
            'staff_id' => 'HACKED99',
            'full_name' => 'Updated Name',
            'phone' => '0240000000',
            'email' => 'updated@example.test',
            'gender' => 'Female',
            'department' => 'Science',
            'position' => 'Teacher',
            'employment_status' => 'Active',
            'date_joined' => '2020-01-01',
            'association_joined_at' => '2021-01-01',
            'notes' => 'Updated by staff member',
        ])->assertSessionHasNoErrors();

        $staff->refresh();
        $this->assertSame('LOCKED01', $staff->staff_id);
        $this->assertSame('Updated Name', $staff->full_name);
        $this->assertSame('Science', $staff->department);
        $this->assertSame('Updated Name', $staff->user->fresh()->name);
        $this->assertSame('updated@example.test', $staff->user->fresh()->email);
    }

    public function test_staff_dashboard_cards_can_be_filtered_by_year(): void
    {
        $staff = $this->createStaffWithUser('YEAR01', 'Year Filter Member');
        $admin = $this->admin();
        DuesPayment::query()->create(['staff_id' => $staff->id, 'payment_year' => 2025, 'payment_month' => 1, 'amount' => 20, 'payment_date' => '2025-01-05', 'recorded_by' => $admin->id]);
        DuesPayment::query()->create(['staff_id' => $staff->id, 'payment_year' => 2026, 'payment_month' => 1, 'amount' => 75, 'payment_date' => '2026-01-05', 'recorded_by' => $admin->id]);

        $this->actingAs($staff->user)->get(route('staff.dashboard', ['year' => 2025]))
            ->assertOk()
            ->assertSee('Showing 2025')
            ->assertViewHas('summary', fn ($summary) => (float) $summary['paid_year'] === 20.0)
            ->assertViewHas('year', 2025);
    }

    public function test_benefit_request_uses_fixed_default_amount_even_if_form_is_tampered(): void
    {
        $staff = $this->createStaffWithUser('BEN001', 'Benefit Applicant');
        $type = BenefitType::query()->where('name', 'Bereavement')->firstOrFail();

        $this->actingAs($staff->user)->post(route('staff.requests.store'), [
            'benefit_type_id' => $type->id,
            'subject' => 'Family bereavement support',
            'description' => 'Requesting the configured welfare support benefit.',
            'requested_amount' => 999999,
            'incident_date' => '2026-07-01',
        ])->assertSessionHasNoErrors();

        $request = BenefitRequest::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame((float) $type->default_amount, (float) $request->requested_amount);
        $this->assertSame(500.0, (float) $request->requested_amount);
    }

    public function test_inactive_session_is_ended_using_system_timeout_setting(): void
    {
        \App\Models\Setting::query()->updateOrCreate(['key' => 'session_timeout_minutes'], ['value' => '1', 'type' => 'integer']);
        $staff = $this->createStaffWithUser('TIME01', 'Timeout Member');
        Carbon::setTestNow('2026-07-11 12:00:00');

        $this->actingAs($staff->user)
            ->withSession(['last_activity_at' => now()->subSeconds(61)->timestamp])
            ->get(route('staff.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your session expired due to inactivity. Please sign in again.');

        $this->assertGuest();
        Carbon::setTestNow();
    }

    public function test_staff_cannot_access_admin_pages(): void
    {
        $staff = $this->createStaffWithUser();

        $this->actingAs($staff->user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_staff_search_returns_live_ajax_results(): void
    {
        $admin = $this->admin();
        Staff::query()->create(['staff_id' => 'AJX001', 'full_name' => 'Unique Ajax Person', 'phone' => '0241000000', 'is_active' => true]);
        Staff::query()->create(['staff_id' => 'OTHER1', 'full_name' => 'Different Person', 'phone' => '0242000000', 'is_active' => true]);

        $this->actingAs($admin)->getJson(route('admin.staff.index', ['search' => 'Unique Ajax']))
            ->assertOk()
            ->assertJsonStructure(['html', 'count'])
            ->assertJsonPath('count', 1)
            ->assertSee('Unique Ajax Person')
            ->assertDontSee('Different Person');
    }

    public function test_staff_cannot_record_dues(): void
    {
        $staff = $this->createStaffWithUser();

        $this->actingAs($staff->user)->post(route('admin.dues.store'), [
            'staff_id' => $staff->id,
            'payment_year' => 2026,
            'payment_month' => 1,
            'amount' => 20,
            'payment_date' => '2026-01-10',
        ])->assertForbidden();
    }

    public function test_admin_can_record_dues_and_monthly_transactions_sum(): void
    {
        $admin = $this->admin();
        $staff = Staff::query()->create(['staff_id' => 'S001', 'full_name' => 'Ama Mensah', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.dues.store'), [
            'staff_id' => $staff->id,
            'payment_year' => 2026,
            'payment_month' => 1,
            'amount' => 10,
            'payment_date' => '2026-01-10',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.dues.store'), [
            'staff_id' => $staff->id,
            'payment_year' => 2026,
            'payment_month' => 1,
            'amount' => 10,
            'payment_date' => '2026-01-20',
        ])->assertSessionHasNoErrors();

        $dues = app(DuesCalculationService::class);

        $this->assertSame(20.0, $dues->totalPaid($staff, 2026, 1));
        $this->assertSame('paid', $dues->monthlyBreakdown($staff, 2026)[1]['status']);
    }

    public function test_overpayment_rolls_forward_across_months_until_exhausted(): void
    {
        $admin = $this->admin();
        $staff = Staff::query()->create(['staff_id' => 'ROLL001', 'full_name' => 'Rollover Member', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.dues.store'), [
            'staff_id' => $staff->id,
            'payment_year' => 2026,
            'payment_month' => 6,
            'amount' => 50,
            'payment_date' => '2026-06-10',
            'reference_number' => 'ROLL-50',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dues_payments', ['staff_id' => $staff->id, 'payment_year' => 2026, 'payment_month' => 6, 'amount' => 20]);
        $this->assertDatabaseHas('dues_payments', ['staff_id' => $staff->id, 'payment_year' => 2026, 'payment_month' => 7, 'amount' => 20]);
        $this->assertDatabaseHas('dues_payments', ['staff_id' => $staff->id, 'payment_year' => 2026, 'payment_month' => 8, 'amount' => 10]);
        $this->assertSame(50.0, (float) DuesPayment::query()->where('staff_id', $staff->id)->sum('amount'));
        $receipt = \App\Models\DuesPaymentReceipt::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame(50.0, (float) $receipt->amount);
        $this->assertSame(3, $receipt->allocations()->count());
    }

    public function test_annual_total_is_calculated_from_transactions(): void
    {
        $admin = $this->admin();
        $staff = Staff::query()->create(['staff_id' => 'S002', 'full_name' => 'Kojo Boateng', 'is_active' => true]);
        foreach ([1 => 20, 2 => 15, 3 => 25] as $month => $amount) {
            DuesPayment::query()->create([
                'staff_id' => $staff->id,
                'payment_year' => 2026,
                'payment_month' => $month,
                'amount' => $amount,
                'payment_date' => "2026-0{$month}-10",
                'recorded_by' => $admin->id,
            ]);
        }

        $this->assertSame(60.0, app(DuesCalculationService::class)->totalPaid($staff, 2026));
    }

    public function test_staff_only_sees_own_benefit_requests(): void
    {
        $owner = $this->createStaffWithUser('S003', 'Owner Staff');
        $other = $this->createStaffWithUser('S004', 'Other Staff');
        $type = BenefitType::query()->first();
        $request = BenefitRequest::query()->create([
            'staff_id' => $other->id,
            'benefit_type_id' => $type->id,
            'subject' => 'Private request',
            'description' => 'Confidential details',
            'status' => BenefitRequest::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->actingAs($owner->user)->get(route('staff.requests.show', $request))->assertForbidden();
    }

    public function test_staff_can_submit_benefit_request(): void
    {
        $staff = $this->createStaffWithUser();
        $type = BenefitType::query()->first();

        $this->actingAs($staff->user)->post(route('staff.requests.store'), [
            'benefit_type_id' => $type->id,
            'subject' => 'Hospital support',
            'description' => 'Requesting support after hospitalisation.',
            'requested_amount' => 200,
        ])->assertRedirect();

        $this->assertDatabaseHas('benefit_requests', [
            'staff_id' => $staff->id,
            'subject' => 'Hospital support',
            'status' => BenefitRequest::STATUS_SUBMITTED,
        ]);
    }

    public function test_admin_can_approve_request_and_only_one_benefit_is_created(): void
    {
        $admin = $this->admin();
        $staff = $this->createStaffWithUser();
        $type = BenefitType::query()->first();
        $request = BenefitRequest::query()->create([
            'staff_id' => $staff->id,
            'benefit_type_id' => $type->id,
            'subject' => 'Bereavement support',
            'description' => 'Family bereavement support request.',
            'requested_amount' => 500,
            'status' => BenefitRequest::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($admin)->post(route('admin.benefit-requests.review', $request), [
                'status' => BenefitRequest::STATUS_APPROVED,
                'approved_amount' => 500,
                'review_notes' => 'Approved',
            ])->assertRedirect();
        }

        $this->assertSame(1, Benefit::query()->where('staff_id', $staff->id)->count());
        $this->assertNotNull($request->fresh()->resulting_benefit_id);
    }

    public function test_export_totals_match_database_totals(): void
    {
        $admin = $this->admin();
        $staff = Staff::query()->create(['staff_id' => 'S005', 'full_name' => 'Export Staff', 'is_active' => true]);
        DuesPayment::query()->create([
            'staff_id' => $staff->id,
            'payment_year' => 2026,
            'payment_month' => 1,
            'amount' => 20,
            'payment_date' => '2026-01-01',
            'recorded_by' => $admin->id,
        ]);
        DuesPayment::query()->create([
            'staff_id' => $staff->id,
            'payment_year' => 2026,
            'payment_month' => 2,
            'amount' => 30,
            'payment_date' => '2026-02-01',
            'recorded_by' => $admin->id,
        ]);

        $rows = (new AnnualDuesChartExport(2026))->array();
        $staffRow = collect($rows)->first(fn ($row) => ($row[1] ?? null) === 'S005');

        $this->assertSame(50.0, (float) $staffRow[15]);
    }

    public function test_annual_benefits_chart_uses_monthly_matrix_with_staff_id(): void
    {
        $admin = $this->admin();
        $staff = Staff::query()->create(['staff_id' => 'S006', 'full_name' => 'Benefit Member', 'is_active' => true]);
        $type = BenefitType::query()->firstOrFail();
        Benefit::query()->create([
            'staff_id' => $staff->id,
            'benefit_type_id' => $type->id,
            'title' => 'Paid support',
            'amount' => 350,
            'payment_date' => '2026-03-15',
            'status' => Benefit::STATUS_PAID,
            'created_by' => $admin->id,
            'paid_by' => $admin->id,
        ]);

        $rows = (new AnnualBenefitsChartExport(2026))->array();
        $staffRow = collect($rows)->first(fn ($row) => ($row[1] ?? null) === 'S006');

        $this->assertSame('Benefit Member', $staffRow[2]);
        $this->assertSame(350.0, (float) $staffRow[5]);
        $this->assertSame(350.0, (float) $staffRow[15]);
    }

    public function test_unauthorized_users_cannot_delete_financial_records(): void
    {
        $admin = $this->admin();
        $staff = $this->createStaffWithUser();
        $payment = DuesPayment::query()->create([
            'staff_id' => $staff->id,
            'payment_year' => 2026,
            'payment_month' => 1,
            'amount' => 20,
            'payment_date' => '2026-01-01',
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($staff->user)->post(route('admin.dues.deletion-request', $payment), [
            'reason' => 'Not allowed',
            'password' => 'password',
        ])->assertForbidden();
    }

    public function test_payment_deletion_requires_password_and_second_admin_approval(): void
    {
        $requester = $this->admin();
        $approver = User::factory()->create(['name' => 'Second Admin', 'username' => 'second-admin', 'password' => Hash::make('approval-pass')]);
        $approver->assignRole(Role::findByName('Administrator'));
        $approver->syncPermissions([Permission::findByName('manage dues')]);
        $staff = Staff::query()->create(['staff_id' => 'DEL001', 'full_name' => 'Deletion Test', 'is_active' => true]);
        $payment = DuesPayment::query()->create([
            'staff_id' => $staff->id, 'payment_year' => 2026, 'payment_month' => 1,
            'amount' => 20, 'payment_date' => '2026-01-01', 'recorded_by' => $requester->id,
        ]);

        $this->actingAs($requester)->post(route('admin.dues.deletion-request', $payment), [
            'reason' => 'Duplicate payment', 'password' => 'wrong-password',
        ])->assertSessionHasErrors('password');
        $this->assertFalse($payment->fresh()->trashed());

        $this->actingAs($requester)->post(route('admin.dues.deletion-request', $payment), [
            'reason' => 'Duplicate payment', 'password' => 'ChangeMe123!',
        ])->assertSessionHasNoErrors();
        $deletionRequest = DuesPaymentDeletionRequest::query()->firstOrFail();
        $this->assertFalse($payment->fresh()->trashed());

        $this->actingAs($requester)->post(route('admin.dues.deletion-requests.approve', $deletionRequest), [
            'password' => 'ChangeMe123!',
        ])->assertForbidden();

        $this->actingAs($approver)->post(route('admin.dues.deletion-requests.approve', $deletionRequest), [
            'password' => 'approval-pass',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($payment->fresh()->trashed());
        $this->assertSame('approved', $deletionRequest->fresh()->status);
        $this->assertSame($approver->id, $deletionRequest->fresh()->reviewed_by);
    }

    public function test_debug_mode_allows_password_confirmed_deletion_without_second_admin(): void
    {
        \App\Models\Setting::query()->updateOrCreate(['key' => 'system_mode'], ['value' => 'debug', 'type' => 'string']);
        $admin = $this->admin();
        $staff = Staff::query()->create(['staff_id' => 'DEMO01', 'full_name' => 'Demo Data', 'is_active' => true]);
        $payment = DuesPayment::query()->create([
            'staff_id' => $staff->id, 'payment_year' => 2026, 'payment_month' => 1,
            'amount' => 20, 'payment_date' => '2026-01-01', 'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('admin.dues.deletion-request', $payment), [
            'reason' => 'Remove demo data', 'password' => 'ChangeMe123!',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($payment->fresh()->trashed());
        $this->assertDatabaseCount('dues_payment_deletion_requests', 0);
        $this->assertStringContainsString('[Debug mode]', $payment->fresh()->deleted_reason);
    }

    public function test_deleting_a_rollover_receipt_removes_every_monthly_allocation(): void
    {
        \App\Models\Setting::query()->updateOrCreate(['key' => 'system_mode'], ['value' => 'debug', 'type' => 'string']);
        $admin = $this->admin();
        $staff = Staff::query()->create(['staff_id' => 'GROUP01', 'full_name' => 'Grouped Payment', 'is_active' => true]);
        $allocations = app(\App\Services\DuesPaymentAllocationService::class)->record([
            'staff_id'=>$staff->id,'payment_year'=>2026,'payment_month'=>1,'amount'=>50,'payment_date'=>'2026-01-10',
        ], $admin->id);
        $receiptId = $allocations[0]->receipt_id;

        $this->actingAs($admin)->post(route('admin.dues.deletion-request', $allocations[0]), [
            'reason'=>'Delete complete demo receipt','password'=>'ChangeMe123!',
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, DuesPayment::withTrashed()->where('receipt_id',$receiptId)->whereNotNull('deleted_at')->count());
        $this->assertNotNull(\App\Models\DuesPaymentReceipt::withTrashed()->findOrFail($receiptId)->deleted_at);
    }

    public function test_debug_mode_can_delete_staff_and_disable_login(): void
    {
        \App\Models\Setting::query()->updateOrCreate(['key'=>'system_mode'], ['value'=>'debug','type'=>'string']);
        $admin=$this->admin(); $staff=$this->createStaffWithUser('DEMO-STF','Demo Staff'); $userId=$staff->user_id;
        $this->actingAs($admin)->post(route('admin.staff.deletion-request',$staff), ['reason'=>'Demo record','password'=>'ChangeMe123!'])->assertSessionHasNoErrors();
        $this->assertNotNull(Staff::withTrashed()->findOrFail($staff->id)->deleted_at);
        $this->assertSame('inactive', User::findOrFail($userId)->status);
    }

    public function test_system_mode_switch_requires_admin_password(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.settings.mode'), ['system_mode' => 'debug', 'password' => 'wrong'])
            ->assertSessionHasErrors('password');
        $this->assertSame('production', \App\Models\Setting::value('system_mode', 'production'));

        $this->actingAs($admin)->post(route('admin.settings.mode'), ['system_mode' => 'debug', 'password' => 'ChangeMe123!'])
            ->assertSessionHasNoErrors();
        $this->assertSame('debug', \App\Models\Setting::value('system_mode'));
    }

    public function test_import_does_not_silently_merge_uncertain_name_matches(): void
    {
        Staff::query()->create(['staff_id' => 'A1', 'full_name' => 'Same Name', 'is_active' => true]);
        Staff::query()->create(['staff_id' => 'A2', 'full_name' => 'Same Name', 'is_active' => true]);
        $file = $this->workbookUpload([
            ['No.', 'Names of Members', 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEPT', 'OCT', 'NOV', 'DEC', 'TOTAL PAYMENT'],
            [1, 'Same Name', 20, 20, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 40],
        ]);

        $batch = app(ExcelImportService::class)->preview($file, 2026, $this->admin()->id);

        $this->assertDatabaseHas('import_batch_rows', [
            'import_batch_id' => $batch->id,
            'full_name' => 'Same Name',
            'status' => 'manual_review',
        ]);
    }

    public function test_admin_can_manually_resolve_import_staff_and_monthly_amounts(): void
    {
        $admin = $this->admin();
        $staff = Staff::query()->create(['staff_id' => 'MATCH01', 'full_name' => 'Correct Member', 'is_active' => true]);
        $batch = ImportBatch::query()->create([
            'original_filename' => 'dues.xlsx',
            'uploaded_by' => $admin->id,
            'status' => 'committed',
            'manual_review_count' => 1,
            'summary' => ['year' => 2026],
        ]);
        $row = ImportBatchRow::query()->create([
            'import_batch_id' => $batch->id,
            'row_number' => 8,
            'full_name' => 'Wrong Name',
            'monthly_amounts' => [1 => 20],
            'reported_total' => 20,
            'status' => 'manual_review',
            'message' => 'No safe staff match found.',
        ]);

        $this->actingAs($admin)->post(route('admin.import.rows.resolve', [$batch, $row]), [
            'matched_staff_id' => $staff->id,
            'monthly_amounts' => [1 => 25, 2 => 15],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('import_batch_rows', [
            'id' => $row->id,
            'matched_staff_id' => $staff->id,
            'status' => 'matched',
            'reported_total' => 40,
        ]);
        $this->assertSame(0, $batch->fresh()->manual_review_count);
    }

    public function test_dues_format_matches_titled_staff_name_and_imports_months(): void
    {
        $admin = $this->admin();
        $staff = Staff::query()->create([
            'staff_id' => '182737',
            'full_name' => 'SAMUEL KOFI AGUDOGO',
            'is_active' => true,
        ]);
        $file = $this->workbookUpload([
            ['ADISADEL COLLEGE TEACHING STAFF WELFARE ASSOCIATION JANUARY-DECEMBER, 2024 DUES PAYMENT CHART'],
            [null, 'NAMES OF MEMBERS', 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEPT', 'OCT', 'NOV', 'DEC', 'TOTAL PAYMENT'],
            [1, 'MR. SAMUEL KOFI AGUDOGO', 20, 20, 20, 20, 20, 20, 20, null, null, null, null, null, 140],
        ]);

        $batch = app(ExcelImportService::class)->preview($file, 2024, $admin->id);
        $row = $batch->rows()->firstOrFail();
        $summary = app(ExcelImportService::class)->commit($batch, $admin->id);

        $this->assertSame($staff->id, $row->matched_staff_id);
        $this->assertSame(7, $summary['payments_created']);
        $this->assertDatabaseHas('dues_payments', [
            'staff_id' => $staff->id,
            'payment_year' => 2024,
            'payment_month' => 7,
            'amount' => 20,
        ]);
    }

    public function test_dues_import_rejects_a_year_that_disagrees_with_workbook_title(): void
    {
        $file = $this->workbookUpload([
            ['JANUARY-DECEMBER, 2024 DUES PAYMENT CHART'],
            ['NAMES OF MEMBERS', 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEPT', 'OCT', 'NOV', 'DEC', 'TOTAL PAYMENT'],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('workbook title indicates 2024');
        app(ExcelImportService::class)->preview($file, 2025, $this->admin()->id);
    }

    public function test_staff_workbook_import_creates_login_using_staff_id_and_phone(): void
    {
        $file = $this->workbookUpload([
            ['STAFF INITIALISATION'],
            ['S/N', 'NAME', 'STAFF ID', 'PHONE NUMBER'],
            [null, null, null, null],
            [1, 'Ama Mensah', '182737', '0243120588'],
        ]);

        $summary = app(StaffExcelImportService::class)->import($file);
        $user = User::query()->where('username', '182737')->firstOrFail();

        $this->assertSame(1, $summary['staff_created']);
        $this->assertSame(1, $summary['accounts_created']);
        $this->assertTrue(Hash::check('0243120588', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertDatabaseHas('staff', [
            'staff_id' => '182737',
            'phone' => '0243120588',
            'user_id' => $user->id,
        ]);
    }

    public function test_staff_with_temporary_password_cannot_bypass_password_change(): void
    {
        $staff = $this->createStaffWithUser();
        $staff->user->update(['must_change_password' => true]);

        $this->actingAs($staff->user)
            ->get(route('staff.dashboard'))
            ->assertRedirect(route('staff.password.edit'));

        $this->actingAs($staff->user)
            ->get(route('staff.password.edit'))
            ->assertOk();
    }

    public function test_staff_import_skips_duplicate_staff_ids_instead_of_merging_people(): void
    {
        $file = $this->workbookUpload([
            ['S/N', 'NAME', 'STAFF ID', 'PHONE NUMBER'],
            [1, 'First Person', '1438305', '0241111111'],
            [2, 'Second Person', '1438305', '0242222222'],
        ]);

        $summary = app(StaffExcelImportService::class)->import($file);

        $this->assertSame(2, $summary['skipped']);
        $this->assertDatabaseMissing('staff', ['staff_id' => '1438305']);
    }

    private function admin(): User
    {
        return User::query()->where('username', 'admin')->firstOrFail();
    }

    private function createStaffWithUser(string $staffId = 'STF001', string $name = 'Staff User'): Staff
    {
        $user = User::factory()->create([
            'name' => $name,
            'username' => strtolower($staffId),
            'password' => Hash::make('password'),
        ]);
        $user->assignRole(Role::findByName('Staff Member'));

        return Staff::query()->create([
            'user_id' => $user->id,
            'staff_id' => $staffId,
            'full_name' => $name,
            'is_active' => true,
        ])->load('user');
    }

    private function workbookUpload(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValue([$columnIndex + 1, $rowIndex + 1], $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'welfare-import').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'dues.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
