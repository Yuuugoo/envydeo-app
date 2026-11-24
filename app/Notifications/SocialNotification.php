<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Base for social notifications: stored for the in-app bell, and pushed to the
 * person's phone/desktop when they have enabled push notifications.
 */
abstract class SocialNotification extends Notification
{
    public function __construct(public User $actor) {}

    /** Short sentence after the actor's name, e.g. "started following you". */
    abstract protected function text(): string;

    /** Where tapping the notification goes. */
    abstract protected function url(): string;

    /** Groups/replaces push notifications of the same kind on the device. */
    protected function tag(): ?string
    {
        return null;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (method_exists($notifiable, 'pushSubscriptions') && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_username' => $this->actor->username,
            'actor_avatar' => $this->actor->avatarUrl(),
            'actor_initials' => $this->actor->initials(),
            'text' => $this->text(),
            'url' => $this->url(),
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->actor->name)
            ->body($this->text())
            ->icon('/icons/icon-192.png')
            ->badge('/icons/icon-192.png')
            ->tag($this->tag() ?? static::class . ':' . $this->actor->id)
            ->data(['url' => $this->url()]);
    }
}
