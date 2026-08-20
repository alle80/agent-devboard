<?php

namespace Alle80\Griglia\Notifications;

use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Settings\AppSettings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Base of the notifications the BOARD itself sends to the owner of the agent list (the app notifies,
 * not the agent): in-app bell (database), Web Push (browser/phone) and mail — each channel switchable
 * from /settings (AppSettings notify_in_app / notify_webpush / notify_mail).
 */
abstract class GrigliaNotification extends Notification
{
    public function __construct(public Todo $todo) {}

    abstract public function title(): string;

    abstract public function body(): string;

    /** Short type key stored with the database notification (e.g. 'todo_completed'). */
    abstract public function kind(): string;

    /** Emoji shown in the bell / push. */
    public function icon(): string
    {
        return '🤖';
    }

    public function via(object $notifiable): array
    {
        $app = app(AppSettings::class);
        $via = [];
        if ($app->notify_in_app) {
            $via[] = 'database';
        }
        if ($app->notify_webpush && method_exists($notifiable, 'routeNotificationForWebPush') && config('webpush.vapid.public_key')) {
            $via[] = WebPushChannel::class;
        }
        if ($app->notify_mail && ! empty($notifiable->email) && config('mail.default') && config('mail.default') !== 'array') {
            $via[] = 'mail';
        }

        return $via;
    }

    /** URL of the board page where the todo lives (list + modal opened by the query string). */
    public function url(): string
    {
        $prefix = rtrim((string) config('griglia.route_prefix', ''), '/');

        return url(($prefix ?: '').'/?list='.$this->todo->checklist_id.'&open='.$this->todo->id);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind(),
            'icon' => $this->icon(),
            'title' => $this->title(),
            'body' => $this->body(),
            'todo_id' => $this->todo->id,
            'checklist_id' => $this->todo->checklist_id,
            'todo_title' => $this->todo->title,
            'url' => $this->url(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->icon().' '.$this->title())
            ->line($this->body())
            ->action(__('griglia::t.notif.open_task'), $this->url());
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->icon().' '.$this->title())
            ->body($this->body())
            ->icon(asset('vendor/griglia/images/brand/mark-180.png'))
            ->tag('griglia-todo-'.$this->todo->id)
            ->data(['url' => $this->url(), 'todo_id' => $this->todo->id])
            ->options(['TTL' => 3600]);
    }
}
