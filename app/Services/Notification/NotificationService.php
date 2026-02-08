<?php

namespace App\Services\Notification;

use App\Mail\InventoryAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send inventory alert notification
     */
    public function sendInventoryAlert(array $criticalProducts, array $lowProducts, array $summary = []): bool
    {
        try {
            $recipients = $this->getInventoryManagerEmails();

            if (empty($recipients)) {
                Log::warning('No inventory manager emails configured for notifications');
                return false;
            }

            $mail = new InventoryAlert($criticalProducts, $lowProducts, $summary);

            foreach ($recipients as $email) {
                Mail::to($email)->send($mail);
            }

            Log::info('Inventory alert sent successfully', [
                'critical_count' => count($criticalProducts),
                'low_count' => count($lowProducts),
                'recipients' => $recipients
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send inventory alert: ' . $e->getMessage(), [
                'critical_count' => count($criticalProducts),
                'low_count' => count($lowProducts),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Legacy method - maintained for backward compatibility
     */
    public function sendAlert($alertData)
    {
        try {
            Log::info('Inventory Alert', $alertData);
            $this->sendEmailAlert($alertData);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send inventory alert', [
                'error' => $e->getMessage(),
                'alert_data' => $alertData
            ]);
            return false;
        }
    }

    /**
     * Send promotional alert for slow-moving inventory
     */
    public function sendPromotionalAlert(array $slowMovingProducts): bool
    {
        try {
            $recipients = $this->getMarketingEmails();

            if (empty($recipients)) {
                Log::warning('No marketing emails configured for promotional notifications');
                return false;
            }

            // Create promotional email content
            $subject = '📈 Promotional Opportunity - Slow Moving Inventory (' . count($slowMovingProducts) . ' items)';
            $body = $this->buildPromotionalEmailBody($slowMovingProducts);

            foreach ($recipients as $email) {
                Mail::raw($body, function ($message) use ($email, $subject) {
                    $message->to($email)
                           ->subject($subject)
                           ->from(config('mail.from.address'), 'Inventory Management System');
                });
            }

            Log::info('Promotional alert sent successfully', [
                'slow_moving_count' => count($slowMovingProducts),
                'recipients' => $recipients
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send promotional alert: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Legacy email alert method
     */
    private function sendEmailAlert($alertData)
    {
        Log::info('Legacy email alert method called', $alertData);

        // For now, just log the alert. In a real app with user management:
        // $adminUsers = User::whereHas('roles', function($query) {
        //     $query->whereIn('name', ['admin', 'inventory_manager']);
        // })->get();
    }

    /**
     * Get inventory manager email addresses
     */
    private function getInventoryManagerEmails(): array
    {
        // In a real application, these would come from a database or config
        // For testing, we'll use test emails that will be caught by Mailtrap
        return [
            'inventory.manager@yourstore.com',
            'admin@yourstore.com'
        ];
    }

    /**
     * Get marketing team email addresses
     */
    private function getMarketingEmails(): array
    {
        return [
            'marketing@yourstore.com',
            'promotions@yourstore.com'
        ];
    }

    /**
     * Build promotional email body
     */
    private function buildPromotionalEmailBody(array $slowMovingProducts): string
    {
        $body = "🎯 PROMOTIONAL OPPORTUNITY ALERT\n\n";
        $body .= "The following products have been identified as slow-moving inventory that could benefit from promotional campaigns:\n\n";

        foreach ($slowMovingProducts as $product) {
            $body .= "• {$product['name']}\n";
            $body .= "  Stock: {$product['current_stock']} units\n";
            $body .= "  Last Sale: " . ($product['last_sale_date'] ?? 'No recent sales') . "\n";
            $body .= "  Suggested Action: Consider discount campaign or bundle offers\n\n";
        }

        $body .= "💡 RECOMMENDATIONS:\n";
        $body .= "- Create discount campaigns (10-25% off)\n";
        $body .= "- Bundle with fast-moving items\n";
        $body .= "- Feature in newsletter or social media\n";
        $body .= "- Consider seasonal promotions\n\n";

        $body .= "This notification was generated automatically by your Inventory Management System.\n";
        $body .= "Generated on: " . now()->format('Y-m-d H:i:s');

        return $body;
    }
}
