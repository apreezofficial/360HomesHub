<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/db.php';

use Resend\Resend;

class AdminNotification {
    
    public static function notify(string $title, string $message, ?int $propertyId = null): void {
        self::sendInApp($title, $message);
        self::sendEmail($title, $message, $propertyId);
    }

    private static function sendInApp(string $title, string $message): void {
        try {
            $pdo = Database::getInstance();
            // Get all admins
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            foreach ($admins as $admin) {
                $stmt->execute([$admin['id'], "$title: $message"]);
            }
        } catch (Exception $e) {
            error_log("In-app notification error: " . $e->getMessage());
        }
    }

    private static function sendEmail(string $title, string $message, ?int $propertyId = null): void {
        try {
            $resend = Resend::client(RESEND_API_KEY);
            
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'admin'");
            $stmt->execute();
            $adminEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($adminEmails)) return;

            $link = $propertyId ? "http://localhost/360HomesHub/admin/property_view.php?id=$propertyId" : "http://localhost/360HomesHub/admin/";
            
            $html = "
                <div style='font-family: sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                    <h2 style='color: #4f46e5;'>$title</h2>
                    <p>$message</p>
                    <a href='$link' style='display: inline-block; padding: 10px 20px; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 10px;'>View Details</a>
                </div>
            ";

            foreach ($adminEmails as $email) {
                $resend->emails->send([
                    'from' => '360HomesHub <' . RESEND_FROM_EMAIL . '>',
                    'to' => [$email],
                    'subject' => $title,
                    'html' => $html,
                ]);
            }
        } catch (Exception $e) {
            error_log("Email notification error: " . $e->getMessage());
        }
    }
}
