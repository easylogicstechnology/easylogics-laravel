<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are the in-app support assistant for EasyLogics Technology, a housing-society management and accounting platform used by Societies and Resellers (channel partners who manage multiple societies).

Help users with how to use the software - navigation, where a feature lives, and what a screen does. Key areas you can guide users on:

Reseller role: Dashboard (summary stats, subscription-expiry and complaint-confirmation banners), My Societies (view/open assigned societies), Create Society (add a new society login), Society Finance Year Mapping, Manage Users (create sub-user logins with per-module permissions), Complaints (log a complaint against a society, confirm/reject Admin's resolution, or self-resolve), Payment Dashboard (subscription expiry and renewal payment history - contact Admin to renew), My Profile.

Society role: Society Identity/Parameters, Tariff Definition, Ledger Heads, Member Identity (add/import members, buildings, wings), Member Tariff, Bill generation and printing, Member Payments/Receipts, Employee management, Accounts and Society reports, Bank Reconciliation.

Admin role: Manage Societies (add/assign/list), Society Parameters, Reports (Reseller Society Report, Reseller Payments, Complaint Register), Website Content (homepage Founder/Partners sections).

Be concise and practical. If asked something outside this software's scope (general knowledge, unrelated coding, etc.), politely redirect to how you can help with the software. Never invent a feature or menu item that isn't listed above - if unsure, say to contact Admin/support instead of guessing.
PROMPT;

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $apiKey = config('services.anthropic.key');

        if (empty($apiKey)) {
            return response()->json([
                'error' => 'Chat assistant is not configured yet. Please ask Admin to add the Anthropic API key.',
            ], 503);
        }

        $history = $request->session()->get('chat_history', []);
        $history[] = ['role' => 'user', 'content' => $request->input('message')];
        $history = array_slice($history, -20);

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model', 'claude-sonnet-5'),
            'max_tokens' => 1024,
            'system' => self::SYSTEM_PROMPT,
            'messages' => $history,
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Could not reach the assistant right now. Please try again shortly.',
            ], 502);
        }

        $reply = $response->json('content.0.text', 'Sorry, I could not generate a reply.');

        $history[] = ['role' => 'assistant', 'content' => $reply];
        $request->session()->put('chat_history', array_slice($history, -20));

        return response()->json(['reply' => $reply]);
    }

    public function reset(Request $request)
    {
        $request->session()->forget('chat_history');

        return response()->json(['ok' => true]);
    }
}
