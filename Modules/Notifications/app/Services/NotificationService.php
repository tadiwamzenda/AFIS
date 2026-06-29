<?php

namespace Modules\Notifications\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Models\AfisNotification;
use App\Models\User;

class NotificationService
{
    // ─── Core record method ───────────────────────────────────────────────────

    public function record(
        string  $type,
        string  $title,
        string  $message,
        string  $module,
        string  $severity        = 'info',
        ?int    $userId          = null,
        ?object $notifiable      = null,
        array   $data            = [],
        bool    $sendEmail       = false,
        ?string $emailRecipient  = null
    ): AfisNotification {

        $notification = AfisNotification::create([
            'type'             => $type,
            'title'            => $title,
            'message'          => $message,
            'severity'         => $severity,
            'module'           => $module,
            'user_id'          => $userId,
            'notifiable_type'  => $notifiable ? get_class($notifiable) : null,
            'notifiable_id'    => $notifiable?->id,
            'data'             => empty($data) ? null : $data,
            'email_sent'       => false,
        ]);

        if ($sendEmail && $emailRecipient) {
            $this->sendEmail($notification, $emailRecipient);
        }

        return $notification;
    }

    // ─── Predefined notification types ───────────────────────────────────────

    public function simBundleRenewalDue(object $simCard, int $daysUntil): AfisNotification
    {
        return $this->record(
            type:     'sim.bundle_renewal_due',
            title:    "SIM bundle renewal due in {$daysUntil} days",
            message:  "SIM card {$simCard->msisdn} ({$simCard->network_provider}) bundle renews in {$daysUntil} days.",
            module:   'AdmmInventory',
            severity: $daysUntil <= 7 ? 'critical' : 'warning',
            notifiable: $simCard,
            data:     ['msisdn' => $simCard->msisdn, 'days_until' => $daysUntil],
        );
    }

    public function deviceWarrantyExpiring(object $device, int $daysUntil): AfisNotification
    {
        return $this->record(
            type:     'device.warranty_expiring',
            title:    "Device warranty expiring in {$daysUntil} days",
            message:  "GPS device {$device->serial_number} ({$device->model}) warranty expires in {$daysUntil} days.",
            module:   'AdmmInventory',
            severity: $daysUntil <= 7 ? 'critical' : 'warning',
            notifiable: $device,
            data:     ['serial_number' => $device->serial_number, 'days_until' => $daysUntil],
        );
    }

    public function pipelineSyncFailed(object $client, string $error): AfisNotification
    {
        return $this->record(
            type:     'pipeline.sync_failed',
            title:    "Pipeline sync failed for {$client->name}",
            message:  "Navixy data sync failed for client {$client->name}: {$error}",
            module:   'AfisPipeline',
            severity: 'critical',
            notifiable: $client,
            data:     ['client' => $client->name, 'error' => $error],
        );
    }

    public function aiReportGenerated(object $client, string $reportType): AfisNotification
    {
        return $this->record(
            type:     'ai.report_generated',
            title:    'AI report ready',
            message:  ucwords(str_replace('_', ' ', $reportType)) . " report generated for {$client->name}.",
            module:   'AfisEngine',
            severity: 'info',
            notifiable: $client,
            data:     ['client' => $client->name, 'report_type' => $reportType],
        );
    }

    public function incidentAnalysisReady(object $incident): AfisNotification
    {
        return $this->record(
            type:     'incident.analysis_ready',
            title:    'Incident analysis ready',
            message:  "AI analysis complete for incident on {$incident->vehicle_label} ({$incident->incident_date->format('d M Y')}).",
            module:   'AfisIncidents',
            severity: 'info',
            notifiable: $incident,
            data:     ['vehicle' => $incident->vehicle_label],
        );
    }

    // ─── Mark as read ─────────────────────────────────────────────────────────

    public function markAsRead(int $notificationId): void
    {
        AfisNotification::where('id', $notificationId)->update(['read_at' => now()]);
    }

    public function markAllAsRead(int $userId): void
    {
        AfisNotification::where('user_id', $userId)->whereNull('read_at')->update(['read_at' => now()]);
    }

    // ─── Email delivery ───────────────────────────────────────────────────────

    private function sendEmail(AfisNotification $notification, string $recipient): void
    {
        try {
            Mail::send(
                'notifications::emails.notification',
                ['notification' => $notification],
                fn($m) => $m->to($recipient)->subject($notification->title)
            );

            $notification->update(['email_sent' => true]);
        } catch (\Throwable $e) {
            Log::error('Notifications: email send failed', [
                'notification_id' => $notification->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }
}