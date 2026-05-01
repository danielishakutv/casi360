<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreEmailRequest;
use App\Models\AuditLog;
use App\Models\Email;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $recipients = $this->resolveRecipients($data);
        $data['recipient_count'] = $recipients->count();

        if ($recipients->isEmpty()) {
            return $this->error('No recipients matched. Check the audience selection.', 422);
        }

        // Insert as queued first; we update with the real outcome after the
        // delivery loop finishes so the row never claims "sent" before the
        // mailer has actually been called.
        $data['status']  = 'queued';
        $data['sent_at'] = null;
        $email = Email::create($data);

        $delivered = 0;
        $failed    = 0;
        $firstError = null;

        foreach ($recipients as $recipient) {
            try {
                Mail::raw($data['body'], function ($message) use ($recipient, $data) {
                    $message->to($recipient->email)
                            ->subject($data['subject']);
                });
                $delivered++;
            } catch (\Throwable $e) {
                $failed++;
                $firstError ??= $e->getMessage();
                Log::warning("Email delivery failed for {$recipient->email}: {$e->getMessage()}");
            }
        }

        $status = match (true) {
            $delivered === 0                 => 'failed',
            $failed === 0                    => 'delivered',
            default                          => 'partial',
        };

        $email->update([
            'delivered_count' => $delivered,
            'failed_count'    => $failed,
            'error_message'   => $firstError,
            'status'          => $status,
            'sent_at'         => $delivered > 0 ? now() : null,
        ]);

        AuditLog::record(
            auth()->id(),
            'email_sent',
            'email',
            $email->id,
            null,
            $email->fresh()->toApiArray()
        );

        $message = match ($status) {
            'delivered' => "Email delivered to {$delivered} recipient(s)",
            'partial'   => "Email delivered to {$delivered} of {$recipients->count()} recipients ({$failed} failed)",
            default     => 'Email could not be delivered to any recipients',
        };
        $statusCode = $status === 'failed' ? 502 : 201;

        return $this->success([
            'email' => $email->fresh()->toApiArray(),
        ], $message, $statusCode);
    }

    /**
     * Resolve User recipients from the request payload. Centralised so the
     * resolution rules don't drift between this controller and SmsController.
     */
    private function resolveRecipients(array $data)
    {
        $query = User::whereNotNull('email')->where('email', '!=', '');

        if ($data['audience'] === 'individual') {
            $query->whereIn('id', $data['recipient_ids'] ?? []);
        } elseif ($data['audience'] === 'department') {
            $query->whereHas('employee', function ($q) use ($data) {
                $q->whereIn('department_id', $data['department_ids'] ?? []);
            });
        }

        return $query->get();
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
