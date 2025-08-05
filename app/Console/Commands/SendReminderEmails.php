<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendReminderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-emails {--days=7 : Number of days ahead to check for reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email notifications for upcoming and overdue reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $this->info("Checking for reminders in the next {$days} days...");

        // Get upcoming reminders that haven't been sent
        $upcomingReminders = Reminder::upcoming($days)
            ->where('email_sent', false)
            ->get();

        // Get overdue reminders that haven't been sent
        $overdueReminders = Reminder::overdue()
            ->where('email_sent', false)
            ->get();

        $totalSent = 0;

        // Send emails for upcoming reminders
        foreach ($upcomingReminders as $reminder) {
            $this->sendReminderEmail($reminder, 'upcoming');
            $reminder->update(['email_sent' => true]);
            $totalSent++;
        }

        // Send emails for overdue reminders
        foreach ($overdueReminders as $reminder) {
            $this->sendReminderEmail($reminder, 'overdue');
            $reminder->update(['email_sent' => true]);
            $totalSent++;
        }

        $this->info("Emails sent: {$totalSent}");
        $this->info("Upcoming reminders: {$upcomingReminders->count()}");
        $this->info("Overdue reminders: {$overdueReminders->count()}");

        return Command::SUCCESS;
    }

    /**
     * Send reminder email
     */
    private function sendReminderEmail(Reminder $reminder, string $type)
    {
        $subject = $type === 'overdue' 
            ? "🚨 Recordatorio Vencido: {$reminder->title}"
            : "⏰ Recordatorio Próximo: {$reminder->title}";

        $daysUntilDue = $reminder->getDaysUntilDue();
        $status = $daysUntilDue < 0 ? 'vencido hace ' . abs($daysUntilDue) . ' días' : 'vence en ' . $daysUntilDue . ' días';

        try {
            Mail::send('emails.reminder', [
                'reminder' => $reminder,
                'type' => $type,
                'status' => $status,
                'daysUntilDue' => $daysUntilDue
            ], function ($message) use ($reminder, $subject) {
                $message->to(config('mail.reminder_recipient', 'admin@example.com'))
                    ->subject($subject);
            });

            $this->line("Email sent for: {$reminder->title} ({$status})");
        } catch (\Exception $e) {
            $this->error("Failed to send email for {$reminder->title}: {$e->getMessage()}");
        }
    }
}
