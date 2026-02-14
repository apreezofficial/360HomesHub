<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Result - 360HomesHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            animation: scaleIn 0.5s ease-out 0.2s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .icon.success {
            background: #10b981;
            color: white;
        }

        .icon.error {
            background: #ef4444;
            color: white;
        }

        .icon.failed {
            background: #f59e0b;
            color: white;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .message {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .details {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #6b7280;
            font-size: 14px;
        }

        .detail-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
        }

        .close-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }

        .close-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .close-btn:active {
            transform: translateY(0);
        }

        .footer-text {
            margin-top: 20px;
            font-size: 13px;
            color: #9ca3af;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 24px;
            }

            .icon {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        $status = $_GET['status'] ?? 'error';
        $message = $_GET['message'] ?? '';
        $booking_id = $_GET['booking_id'] ?? null;
        $amount = $_GET['amount'] ?? null;

        if ($status === 'success') {
            echo '<div class="icon success">✓</div>';
            echo '<h1>Payment Successful!</h1>';
            echo '<p class="message">Your payment has been verified and your booking is now confirmed.</p>';
            
            if ($booking_id && $amount) {
                echo '<div class="details">';
                echo '<div class="detail-row">';
                echo '<span class="detail-label">Booking ID</span>';
                echo '<span class="detail-value">#' . htmlspecialchars($booking_id) . '</span>';
                echo '</div>';
                echo '<div class="detail-row">';
                echo '<span class="detail-label">Amount Paid</span>';
                echo '<span class="detail-value">₦' . number_format($amount, 2) . '</span>';
                echo '</div>';
                echo '<div class="detail-row">';
                echo '<span class="detail-label">Status</span>';
                echo '<span class="detail-value" style="color: #10b981;">Confirmed</span>';
                echo '</div>';
                echo '</div>';
            }
        } elseif ($status === 'failed') {
            echo '<div class="icon failed">⚠</div>';
            echo '<h1>Payment Failed</h1>';
            echo '<p class="message">' . ($message ? htmlspecialchars($message) : 'Your payment could not be processed. Please try again.') . '</p>';
        } else {
            echo '<div class="icon error">✕</div>';
            echo '<h1>Payment Error</h1>';
            echo '<p class="message">' . ($message ? htmlspecialchars($message) : 'An error occurred while processing your payment.') . '</p>';
        }
        ?>

        <button class="close-btn" onclick="closeWindow()">Close & Return to App</button>
        
        <p class="footer-text">You can safely close this window now</p>
    </div>

    <script>
        function closeWindow() {
            // Try to close the window
            if (window.opener) {
                window.close();
            } else {
                // If can't close, redirect to home or app
                window.location.href = '/360HomesHub/';
            }
        }

        // Auto-close after 10 seconds for success
        <?php if ($status === 'success'): ?>
        setTimeout(function() {
            closeWindow();
        }, 10000);
        <?php endif; ?>
    </script>
</body>
</html>
