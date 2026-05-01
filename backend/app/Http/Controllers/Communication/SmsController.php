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
        // Gate: refuse to pretend we sent anything when no SMS gateway is
        // configured. Once an integration ships, this guard goes away.
        $driver = config('services.sms.driver');
        if (empty($driver)) {
            return $this->error(
                'SMS sending is not configured. Ask your administrator to set SMS_DRIVER in the server environment.',
                503
            );
        }

        // We have a configured driver but no implementation has shipped
        // yet — fail loudly so the UI doesn't display a fake success.
        return $this->error(
            "SMS driver '{$driver}' is configured but no implementation is wired in this build. Contact support.",
            501
        );

        /*
         * IMPLEMENTATION NOTE — to be enabled per-driver:
         *
         * $data = $request->validated();
         * $data['sent_by'] = auth()->id();
         * $recipients = $this->resolveRecipients($data);
         * $data['recipient_count'] = $recipients->count();
         * $data['status']  = 'queued';
         * $sms = SmsMessage::create($data);
         *
         * $delivered = 0; $failed = 0; $firstError = null;
         * foreach ($recipients as $recipient) {
         *     $phone = $recipient->employee?->phone;
         *     if (!$phone) { $failed++; continue; }
         *     try {
         *         app(\App\Services\Communication\SmsGateway::class)
         *             ->send($phone, $data['message']);
         *         $delivered++;
         *     } catch (\Throwable $e) {
         *         $failed++; $firstError ??= $e->getMessage();
         *     }
         * }
         *
         * $sms->update([
         *     'delivered_count' => $delivered,
         *     'failed_count'    => $failed,
         *     'error_message'   => $firstError,
         *     'status'          => $delivered === 0 ? 'failed' : ($failed === 0 ? 'delivered' : 'partial'),
         *     'sent_at'         => $delivered > 0 ? now() : null,
         * ]);
         */
    }

    /**
     * Resolve User recipients from the request payload (mirrors EmailController).
     * Reserved for future use when an SMS driver is wired.
     *
     * @phpstan-ignore-next-line method.unused
     */
    private function resolveRecipients(array $data)
    {
        $query = User::whereHas('employee', fn ($q) => $q->whereNotNull('phone'));

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
