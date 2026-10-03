<?php

namespace App\Http\Controllers;

use App\Models\Founder;
use App\Models\Partner;
use App\Support\HomePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Public landing page (CakePHP: UsersController::home / submitEnquiry).
 * The login form is a modal on this page; a failed login comes back here
 * (GET /login renders the same page) with the modal re-opened.
 */
class HomeController extends Controller
{
    public function index(Request $request)
    {
        $loginMessage = session('error') ?: ($request->session()->get('errors')?->first());

        return view('home', [
            'glanceStats' => $this->glanceStats(),
            'founder' => Founder::where('display_status', 1)->orderBy('id')->first(),
            'partners' => Partner::where('display_status', 1)->orderBy('sort_order')->get(),
            'loginMessage' => $loginMessage,
        ]);
    }

    /**
     * Ajax-only enquiry form: e-mails the submission to the sales inbox,
     * nothing is stored in the database.
     */
    public function submitEnquiry(Request $request): JsonResponse
    {
        foreach (['society_name', 'phone', 'first_name'] as $field) {
            if (trim((string) $request->input($field)) === '') {
                return response()->json(['error' => 1, 'message' => 'Please fill in all required fields.']);
            }
        }

        $lines = [
            'Society / Apartment / Villa Community: ' . e($request->input('society_name')),
            'City: ' . e($request->input('city', '')),
            'Phone: ' . e($request->input('phone')),
            'Email: ' . e($request->input('email', '')),
            'What best describes you: ' . e($request->input('describes_you', '')),
            'Name: ' . e($request->input('first_name') . ' ' . $request->input('last_name', '')),
        ];
        $subject = 'New Enquiry - ' . preg_replace('/[\r\n]+/', ' ', (string) $request->input('society_name'));

        try {
            Mail::html(implode('<br>', $lines), function ($message) use ($subject) {
                $message->to(config('services.enquiry.to'))->subject($subject);
            });

            return response()->json(['error' => 0, 'message' => 'Thank you! We will get back to you shortly.']);
        } catch (\Throwable $e) {
            Log::error('submitEnquiry: failed to send enquiry email - ' . $e->getMessage());

            return response()->json(['error' => 1, 'message' => 'Could not send your enquiry right now. Please try again later.']);
        }
    }

    /**
     * Live "at a glance" counters, same logic as the admin dashboard. Cached
     * for an hour so the public page never runs these COUNTs on every hit.
     */
    private function glanceStats(): array
    {
        return Cache::remember('home_glance_stats', 3600, fn () => [
            'societies' => DB::table('users')->where('access_level', 2)->count(),
            'members' => DB::table('members')->where('status', 1)->count(),
            'bills' => DB::table('member_bill_generates')->count(),
            'resellers' => DB::table('users')->where('access_level', 3)->count(),
            'buildings' => DB::table('buildings')->count(),
            'wings' => DB::table('wings')->count(),
        ]);
    }
}
