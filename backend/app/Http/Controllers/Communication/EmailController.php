<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreEmailRequest;
use App\Models\AuditLog;
use App\Models\Email;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Email::with('sender');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('audience')) {
            $query->where('audience', $request->audience);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where('subject', 'like', "%{$search}%");
        }

        $query->orderBy('created_at', 'desc');

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $items = $query->get();
            return $this->success([
                'emails' => $items->map->toApiArray(),
                'meta' => ['total' => $items->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'emails' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(StoreEmailRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sent_by'] = auth()->id();

        // Resolve recipients
        $recipientQuery = User::whereNotNull('email');
        if ($data['audience'] === 'individual') {
            $recipientQuery->whereIn('id', $data['recipient_ids'] ?? []);
        } elseif ($data['audience'] === 'department') {
            $recipientQuery->whereHas('employee', function ($q) use ($data) {
                $q->whereIn('department_id', $data['department_ids'] ?? []);
            });
        }
        $recipients = $recipientQuery->get();

        $data['recipient_count'] = $recipients->count();
        $data['status'] = 'sent';
        $data['sent_at'] = now();

        $email = Email::create($data);

        // Send emails (queued if queue driver configured)
        foreach ($recipients as $recipient) {
            try {
                Mail::raw($data['body'], function ($message) use ($recipient, $data) {
                    $message->to($recipient->email)
                            ->subject($data['subject']);
                });
            } catch (\Exception $e) {
                // Log failure but don't abort — partial delivery is acceptable
                \Log::warning("Email delivery failed for {$recipient->email}: {$e->getMessage()}");
            }
        }

        AuditLog::record(
            auth()->id(),
            'email_sent',
            'email',
            $email->id,
            null,
            $email->toApiArray()
        );

        return $this->success([
            'email' => $email->toApiArray(),
        ], "Email sent to {$recipients->count()} recipients", 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $email = Email::findOrFail($id);
        $data = $email->toApiArray();

        $email->delete();

        AuditLog::record(
            auth()->id(),
            'email_deleted',
            'email',
            $id,
            $data,
            null
        );

        return $this->success(null, 'Email record deleted successfully');
    }
}
