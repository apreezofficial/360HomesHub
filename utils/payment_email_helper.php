<?php

require_once __DIR__ . '/../utils/email.php';
require_once __DIR__ . '/../utils/activity_logger.php';
require_once __DIR__ . '/../config/env.php';

class PaymentEmailHelper {
    
    /**
     * Send payment confirmation emails to Guest, Host, and Admin
     * 
     * @param PDO $pdo Database connection
     * @param int $bookingId The booking ID
     * @param float $amount The amount paid
     * @param string $reference Payment reference
     * @param string $gateway Payment gateway used
     * @return void
     */
    public static function sendPaymentEmails($pdo, $bookingId, $amount, $reference, $gateway) {
        // Fetch full booking details including guest, host, and property info
        $sql = "
            SELECT 
                b.id, b.check_in, b.check_out, b.guests,
                g.id as guest_id, g.email as guest_email, g.first_name as guest_name, g.last_name as guest_lastname,
                h.id as host_id, h.email as host_email, h.first_name as host_name,
                p.name as property_name, p.address as property_address
            FROM bookings b
            JOIN users g ON b.guest_id = g.id
            JOIN users h ON b.host_id = h.id
            JOIN properties p ON b.property_id = p.id
            WHERE b.id = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookingId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            error_log("PaymentEmailHelper: Booking #$bookingId not found for emailing.");
            return;
        }

        $formattedAmount = number_format($amount, 2);
        $appUrl = defined('APP_URL') ? APP_URL : 'http://localhost/360HomesHub';
        $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@360homeshub.com'; // Fallback or config
        
        // 1. Send Email to GUEST
        if (!empty($data['guest_email'])) {
            $guestSubject = "✅ Payment Successful! Booking #{$bookingId} Confirmed";
            $guestHtml = "
                <div style='font-family: sans-serif; max-width: 600px; margin: auto;'>
                    <h2 style='color: #4F46E5;'>Payment Successful!</h2>
                    <p>Hi " . htmlspecialchars($data['guest_name']) . ",</p>
                    <p>We received your payment of <strong>₦{$formattedAmount}</strong> via " . ucfirst($gateway) . ".</p>
                    <p>Your booking for <strong>" . htmlspecialchars($data['property_name']) . "</strong> is now confirmed.</p>
                    
                    <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <p><strong>Check-in:</strong> {$data['check_in']}</p>
                        <p><strong>Check-out:</strong> {$data['check_out']}</p>
                        <p><strong>Reference:</strong> {$reference}</p>
                    </div>
                    
                    <a href='{$appUrl}/bookings/view.php?id={$bookingId}' style='background: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Booking</a>
                </div>
            ";
            
            $sent = send_email($data['guest_email'], null, $guestSubject, $guestHtml);
            self::logEmail($data['guest_id'], 'guest', $sent, $bookingId);
        }

        // 2. Send Email to HOST
        if (!empty($data['host_email'])) {
            $hostSubject = "💰 New Payment Received! Booking #{$bookingId} Confirmed";
            $hostHtml = "
                <div style='font-family: sans-serif; max-width: 600px; margin: auto;'>
                    <h2 style='color: #10B981;'>Booking Confirmed!</h2>
                    <p>Hi " . htmlspecialchars($data['host_name']) . ",</p>
                    <p>Good news! Payment of <strong>₦{$formattedAmount}</strong> has been received for your property <strong>" . htmlspecialchars($data['property_name']) . "</strong>.</p>
                    
                    <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <p><strong>Guest:</strong> " . htmlspecialchars($data['guest_name'] . ' ' . $data['guest_lastname']) . "</p>
                        <p><strong>Check-in:</strong> {$data['check_in']}</p>
                        <p><strong>Check-out:</strong> {$data['check_out']}</p>
                    </div>
                    
                    <a href='{$appUrl}/host/bookings.php?id={$bookingId}' style='background: #10B981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Manage Booking</a>
                </div>
            ";
            
            $sent = send_email($data['host_email'], null, $hostSubject, $hostHtml);
            self::logEmail($data['host_id'], 'host', $sent, $bookingId);
        }

        // 3. Send Email to ADMIN
        // Fetch all admins or just use the config email
        // For now, let's just send to the main admin email defined in config
        if ($adminEmail) {
            $adminSubject = "🛡️ [Admin] Payment Received: Booking #{$bookingId}";
            $adminHtml = "
                <div style='font-family: sans-serif; max-width: 600px; margin: auto;'>
                    <h2>New Payment Alert</h2>
                    <p>A payment of <strong>₦{$formattedAmount}</strong> was successfully processed via " . ucfirst($gateway) . ".</p>
                    
                    <ul>
                        <li><strong>Booking ID:</strong> #{$bookingId}</li>
                        <li><strong>Property:</strong> " . htmlspecialchars($data['property_name']) . "</li>
                        <li><strong>Guest:</strong> " . htmlspecialchars($data['guest_name'] . ' ' . $data['guest_lastname']) . "</li>
                        <li><strong>Host:</strong> " . htmlspecialchars($data['host_name']) . "</li>
                        <li><strong>Reference:</strong> {$reference}</li>
                    </ul>
                    
                    <a href='{$appUrl}/admin/transactions.php' style='color: #4F46E5;'>View Transaction</a>
                </div>
            ";
            
            $sent = send_email($adminEmail, null, $adminSubject, $adminHtml);
            self::logEmail(1, 'admin', $sent, $bookingId); // Assuming User ID 1 is system/admin
        }
    }

    private static function logEmail($userId, $recipientType, $result, $bookingId) {
        if ($result === true) {
            ActivityLogger::log(
                $userId, 
                'email_sent', 
                "Payment confirmation email sent to {$recipientType}", 
                'email', 
                $bookingId, 
                ['recipient_type' => $recipientType]
            );
        } else {
            ActivityLogger::log(
                $userId, 
                'email_failed', 
                "Failed to send email to {$recipientType}: " . json_encode($result), 
                'email', 
                $bookingId, 
                ['error' => $result]
            );
        }
    }
}
