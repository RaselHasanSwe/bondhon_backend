<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AdminSidebarService
{
    /**
     * Fetch all sidebar badge counts in a single database round-trip.
     *
     * @return array{
     *     pending_photos: int,
     *     pending_reports: int,
     *     pending_disable_requests: int,
     *     new_messages: int,
     *     unread_notifications: int,
     * }
     */
    public function getBadgeCounts(): array
    {
        $adminTypes   = NotificationService::ADMIN_PANEL_NOTIFICATION_TYPES;
        $placeholders = implode(',', array_fill(0, count($adminTypes), '?'));

        $row = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM profile_photos WHERE moderation_status = 'pending') AS pending_photos,
                (SELECT COUNT(*) FROM reports WHERE status = 'pending') AS pending_reports,
                (SELECT COUNT(*) FROM account_disable_requests WHERE status = 'pending') AS pending_disable_requests,
                (SELECT COUNT(*) FROM contact_messages WHERE status = 'new') AS new_messages,
                (SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND type IN ({$placeholders})) AS unread_notifications
        ", $adminTypes);

        return [
            'pending_photos'           => (int) $row->pending_photos,
            'pending_reports'          => (int) $row->pending_reports,
            'pending_disable_requests' => (int) $row->pending_disable_requests,
            'new_messages'             => (int) $row->new_messages,
            'unread_notifications'   => (int) $row->unread_notifications,
        ];
    }
}
