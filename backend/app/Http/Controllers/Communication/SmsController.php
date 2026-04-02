<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreSmsRequest;
use App\Models\AuditLog;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SmsMessage::with('sender');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('audience')) {
            $query->where('audience', $request->audience);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where('message', 'like', "%{$search}%");
        }

        $query->orderBy('created_at', 'desc');

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $items = $query->get();
            return $this->success([
                'sms_messages' => $items->map->toApiArray(),
                'meta' => ['total' => $items->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'sms_messages' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(StoreSmsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sent_by'] = auth()->id();

        // Resolve recipients
        $recipientQuery = User::whereNotNull('id');
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

        $sms = SmsMessage::create($data);

        // SMS gateway integration placeholder
        // Configure in .env: SMS_DRIVER=termii|twilio|africastalking
        // Each recipient's phone can be fetched via $recipient->employee->phone
        // Actual sending would be dispatched as a queued job

        AuditLog::record(
            auth()->id(),
            'sms_sent',
            'sms',
            $sms->id,
            null,
            $sms->toApiArray()
        );

        return $this->success([
            'sms' => $sms->toApiArray(),
        ], "SMS queued for {$recipients->count()} recipients", 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $sms = SmsMessage::findOrFail($id);
        $data = $sms->toApiArray();

        $sms->delete();

        AuditLog::record(
            auth()->id(),
            'sms_deleted',
            'sms',
            $id,
            $data,
            null
        );

        return $this->success(null, 'SMS record deleted successfully');
    }
}
