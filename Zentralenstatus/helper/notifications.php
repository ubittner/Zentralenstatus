<?php

declare(strict_types=1);

trait notifications
{
    /**
     * @param bool $State
     * false =  connection lost
     * true =   connection established or restored
     * @return void
     * @throws Exception
     */
    public function Notify(bool $State): void
    {
        //Do some checks first
        if ($this->CheckMaintenance()) {
            return;
        }

        $timeStamp = date('d.m.Y, H:i:s', time());

        $notificationTypes = ['Notification', 'PushNotification', 'PostNotification', 'MailerNotification'];
        foreach ($notificationTypes as $notificationType) {
            foreach (json_decode($this->ReadPropertyString($notificationType), true) as $notification) {

                // Check use
                if (!$notification['Use']) {
                    continue;
                }
                if (!$this->CheckObjectID($notification['ID'])) {
                    continue;
                }

                // Text
                $text = $notification['ConnectionLostText'] . ' ' . $timeStamp;
                if ($State) {
                    $text = $notification['ConnectionEstablishedText'] . ' ' . $timeStamp;
                }

                // Title
                if ($notificationType != 'MailerNotification') {
                    $title = $notification['ConnectionLostTitle'];
                    if ($State) {
                        $title = $notification['ConnectionEstablishedTitle'];
                    }
                }

                // Notification
                if ($notificationType == 'Notification' && isset($title)) {
                    $icon = $notification['ConnectionLostIcon'];
                    $duration = $notification['ConnectionLostDisplayDuration'];
                    if ($State) {
                        $icon = $notification['ConnectionEstablishedIcon'];
                        $duration = $notification['ConnectionEstablishedDisplayDuration'];
                    }
                    $this->SendNotification($notification['ID'], $title, $text, $icon, $duration);
                }

                if ($notificationType == 'PushNotification' || $notificationType == 'PostNotification') {
                    // Title length max 32 characters
                    $title = substr($notification['ConnectionLostTitle'], 0, 32);
                    if ($State) {
                        $title = substr($notification['ConnectionEstablishedTitle'], 0, 32);
                    }
                    // Text length max 256 characters
                    $text = "\n" . $text;
                    $text = substr($text, 0, 256);
                }

                // Push notification
                if ($notificationType == 'PushNotification' && isset($title)) {
                    $sound = $notification['ConnectionLostSound'];
                    $targetID = $notification['ConnectionLostTargetID'];
                    if ($State) {
                        $sound = $notification['ConnectionEstablishedSound'];
                        $targetID = $notification['ConnectionEstablishedTargetID'];
                    }

                    $this->SendPushNotification($notification['ID'], $title, $text, $sound, $targetID);
                }

                // Post Notification
                if ($notificationType == 'PostNotification' && isset($title)) {
                    $icon = $notification['ConnectionLostIcon'];
                    $sound = $notification['ConnectionLostSound'];
                    $targetID = $notification['ConnectionLostTargetID'];
                    if ($State) {
                        $icon = $notification['ConnectionEstablishedIcon'];
                        $sound = $notification['ConnectionEstablishedSound'];
                        $targetID = $notification['ConnectionEstablishedTargetID'];
                    }
                    $this->SendPostNotification($notification['ID'], $title, $text, $icon, $sound, $targetID);
                }

                // Mailer notification
                if ($notificationType == 'MailerNotification') {
                    $subject = $notification['ConnectionLostSubject'];
                    if ($State) {
                        $subject = $notification['ConnectionEstablishedSubject'];
                    }
                    $this->SendMail($notification['ID'], $subject, $text);
                }
            }
        }
    }

    ##### Protected

    protected function SendNotification(int $InstanceID, string $Title, string $Text, string $Icon, int $Duration): void
    {
        $scriptText = sprintf(
            'WFC_SendNotification(%d, "%s", "%s", "%s", %d);',
            $InstanceID,
            $Title,
            $Text,
            $Icon,
            $Duration
        );
        @IPS_RunScriptText($scriptText);
    }

    protected function SendPushNotification(int $InstanceID, string $Title, string $Text, string $Sound, int $TargetID): void
    {
        $scriptText = sprintf(
            'WFC_PushNotification(%d, "%s", "%s", "%s", %d);',
            $InstanceID,
            $Title,
            $Text,
            $Sound,
            $TargetID
        );
        @IPS_RunScriptText($scriptText);
    }

    protected function SendPostNotification(int $InstanceID, string $Title, string $Text, string $Icon, string $Sound, int $TargetID): void
    {
        $scriptText = sprintf(
            'VISU_PostNotificationEx(%d, "%s", "%s", "%s", "%s", %d);',
            $InstanceID,
            $Title,
            $Text,
            $Icon,
            $Sound,
            $TargetID
        );
        @IPS_RunScriptText($scriptText);
    }

    protected function SendMail(int $InstanceID, string $Subject, string $Text): void
    {
        $scriptText = sprintf(
            'MA_SendMessage(%d, "%s", "%s");',
            $InstanceID,
            $Subject,
            $Text
        );
        @IPS_RunScriptText($scriptText);
    }
}